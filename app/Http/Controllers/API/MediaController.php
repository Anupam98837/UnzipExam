<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MediaController extends Controller
{
    /* =========================================================
     |  Helpers
     * ========================================================= */

    /** Current actor from CheckRole middleware */
    private function actor(Request $r): array
    {
        return [
            'id'   => (int) ($r->attributes->get('auth_tokenable_id') ?? 0),
            'role' => (string) ($r->attributes->get('auth_role') ?? ''),
        ];
    }

    /** Absolute APP URL (no trailing slash) */
    private function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Ensure public/AllMedia exists; return path */
    private function mediaDir(): string
    {
        $dir = public_path('AllMedia');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }
        return $dir;
    }

    /** Guess category from mime/ext */
    private function categorize(?string $mime, ?string $ext): string
    {
        $mime = strtolower((string) $mime);
        $ext  = strtolower((string) $ext);

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';

        $doc = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','rtf','csv','md','json','xml'];
        $arc = ['zip','rar','7z','tar','gz','bz2'];
        if (in_array($ext, $doc, true)) return 'document';
        if (in_array($ext, $arc, true)) return 'archive';
        return 'other';
    }

    /* =========================================================
     | ✅ Activity log (DocumentType-style)
     * ========================================================= */

    private function logActivity(
        Request $request,
        string $activity,                 // 'store'|'destroy'
        string $module,                   // 'Media'
        string $note,                     // readable note
        string $tableName,                // 'media'
        ?int $recordId = null,
        ?array $changed = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $a = $this->actor($request);

        $changedFields = null;
        if (is_array($changed)) {
            $changedFields = array_values(array_unique(
                array_keys($changed) === range(0, count($changed)-1) ? $changed : array_keys($changed)
            ));
        }

        try {
            DB::table('user_data_activity_log')->insert([
                'performed_by'      => $a['id'] ?: 0,
                'performed_by_role' => $a['role'] ?: null,
                'ip'                => $request->ip(),
                'user_agent'        => (string) $request->userAgent(),
                'activity'          => $activity,
                'module'            => $module,
                'table_name'        => $tableName ?: 'unknown',
                'record_id'         => $recordId,
                'changed_fields'    => $changedFields ? json_encode($changedFields, JSON_UNESCAPED_UNICODE) : null,
                'old_values'        => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values'        => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'log_note'          => $note,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('user_data_activity_log insert failed', ['error' => $e->getMessage()]);
        }
    }

    /** DB-only notification insert (same pattern you used earlier) */
    private function persistNotification(array $payload): void
    {
        try {
            $title     = (string)($payload['title']    ?? 'Notification');
            $message   = (string)($payload['message']  ?? '');
            $receivers = array_values(array_map(function($x){
                return [
                    'id'   => isset($x['id']) ? (int)$x['id'] : null,
                    'role' => (string)($x['role'] ?? 'unknown'),
                    'read' => (int)($x['read'] ?? 0),
                ];
            }, $payload['receivers'] ?? []));

            $metadata = $payload['metadata'] ?? [];
            $type     = (string)($payload['type'] ?? 'general');
            $linkUrl  = $payload['link_url'] ?? null;

            $priority = in_array(($payload['priority'] ?? 'normal'), ['low','normal','high','urgent'], true)
                        ? $payload['priority'] : 'normal';

            $status   = in_array(($payload['status'] ?? 'active'), ['active','archived','deleted'], true)
                        ? $payload['status'] : 'active';

            DB::table('notifications')->insert([
                'title'      => $title,
                'message'    => $message,
                'receivers'  => json_encode($receivers, JSON_UNESCAPED_UNICODE),
                'metadata'   => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                'type'       => $type,
                'link_url'   => $linkUrl,
                'priority'   => $priority,
                'status'     => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('notifications insert failed', ['error' => $e->getMessage()]);
        }
    }

    /** Admin receivers:  (id, role=admin). */
    private function adminReceivers(array $excludeIds = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeIds));
        $rows = DB::table('users')->select('id')->get();
        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r->id;
            if (!isset($exclude[$id])) $out[] = ['id'=>$id, 'role'=>'admin', 'read'=>0];
        }
        return $out;
    }

    /** Allow id or uuid lookup */
    private function findMediaRow(string $idOrUuid, bool $withTrashed = false): ?object
    {
        $q = DB::table('media');
        if ($withTrashed) $q->whereRaw('1=1'); else $q->whereNull('deleted_at');

        if (ctype_digit($idOrUuid)) {
            $q->where('id', (int)$idOrUuid);
        } else {
            $q->where('uuid', $idOrUuid);
        }
        return $q->first();
    }

    /* =========================================================
     |  GET /api/media  (list)
     * ========================================================= */
    public function index(Request $request)
    {
        $v = Validator::make($request->all(), [
            'q'               => 'nullable|string|max:255',
            'category'        => 'nullable|string|in:image,video,audio,document,archive,other',
            'status'          => 'nullable|string|in:active,archived',
            'usage_tag'       => 'nullable|string|max:50',
            'sort'            => 'nullable|string|max:64',
            'per_page'        => 'nullable|integer|min:1|max:200',
            'page'            => 'nullable|integer|min:1',
            'include_deleted' => 'nullable|boolean',
            'only_deleted'    => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $per  = (int) $request->input('per_page', 20);
        $page = (int) $request->input('page', 1);
        $q    = (string) $request->input('q', '');
        $cat  = $request->input('category');
        $stat = $request->input('status');
        $tag  = $request->input('usage_tag');
        $sort = (string) $request->input('sort', '-created_at');

        $includeDeleted = filter_var($request->input('include_deleted', false), FILTER_VALIDATE_BOOLEAN);
        $onlyDeleted    = filter_var($request->input('only_deleted', false), FILTER_VALIDATE_BOOLEAN);

        $builder = DB::table('media as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.created_by')
            ->select('m.*', DB::raw("COALESCE(u.name, CONCAT('User#', m.created_by)) as created_by_name"));

        if ($onlyDeleted) {
            $builder->whereNotNull('m.deleted_at');
        } elseif (!$includeDeleted) {
            $builder->whereNull('m.deleted_at');
        }

        if ($cat)  $builder->where('m.category', $cat);
        if ($stat) $builder->where('m.status', $stat);
        if ($tag)  $builder->where('m.usage_tag', $tag);

        if ($q !== '') {
            try {
                $builder->whereRaw('MATCH(m.title, m.description, m.alt_text) AGAINST (? IN NATURAL LANGUAGE MODE)', [$q]);
            } catch (\Throwable $e) {
                $like = '%'.$q.'%';
                $builder->where(function($w) use ($like) {
                    $w->where('m.title', 'like', $like)
                      ->orWhere('m.description', 'like', $like)
                      ->orWhere('m.alt_text', 'like', $like);
                });
            }
        }

        $dir = 'asc'; $col = 'm.created_at';
        if ($sort) {
            $dir = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $col = ltrim($sort, '+-');
            $allowed = ['id','created_at','title','category','size_bytes','status'];
            $col = in_array($col, $allowed, true) ? "m.$col" : 'm.created_at';
        }
        $builder->orderBy($col, $dir)->orderBy('m.id', 'desc');

        $total = (clone $builder)->count();
        $items = $builder->forPage($page, $per)->get();

        return response()->json([
            'success'    => true,
            'data'       => $items,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $per,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max(1, $per)),
            ],
            'filters_echo' => [
                'q' => $q, 'category' => $cat, 'status' => $stat, 'usage_tag' => $tag,
                'include_deleted' => $includeDeleted, 'only_deleted' => $onlyDeleted,
            ]
        ]);
    }

    /* =========================================================
     |  POST /api/media  (upload & create)
     |  ✅ ADD LOG + NOTIFICATION ONLY HERE
     * ========================================================= */
    public function store(Request $request)
    {
        $actor = $this->actor($request);

        $v = Validator::make($request->all(), [
            'file'        => 'required|file|max:102400',
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'alt_text'    => 'nullable|string|max:255',
            'usage_tag'   => 'nullable|string|max:50',
            'status'      => 'nullable|string|in:active,archived',
            'metadata'    => 'nullable|array',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json(['success' => false, 'error' => 'Invalid file upload'], 422);
        }

        $uuid   = (string) Str::uuid();
        $ext    = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $ext    = preg_replace('/[^a-z0-9]+/i', '', $ext) ?? '';
        $fname  = $uuid . ($ext ? ('.' . $ext) : '');
        $dir    = $this->mediaDir();
        $fpath  = $dir . DIRECTORY_SEPARATOR . $fname;

        $file->move($dir, $fname);

        $mime   = File::mimeType($fpath) ?: $file->getClientMimeType();
        $size   = (int) filesize($fpath);
        $cat    = $this->categorize($mime, $ext);

        $width = null; $height = null; $duration = null;
        if ($cat === 'image') {
            try {
                $dim = @getimagesize($fpath);
                if (is_array($dim)) { $width = (int) $dim[0]; $height = (int) $dim[1]; }
            } catch (\Throwable $e) {}
        }

        $absUrl = $this->appUrl() . '/AllMedia/' . $fname;

        $row = [
            'uuid'             => $uuid,
            'title'            => $request->input('title'),
            'description'      => $request->input('description'),
            'alt_text'         => $request->input('alt_text'),
            'category'         => $cat,
            'mime_type'        => $mime,
            'ext'              => $ext ?: null,
            'size_bytes'       => $size,
            'width'            => $width,
            'height'           => $height,
            'duration_seconds' => $duration,
            'url'              => $absUrl,
            'usage_tag'        => $request->input('usage_tag'),
            'metadata'         => $request->filled('metadata') ? json_encode($request->input('metadata'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'status'           => $request->input('status', 'active'),
            'created_by'       => $actor['id'] ?: null,
            'updated_by'       => $actor['id'] ?: null,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ];

        try {
            $id = DB::table('media')->insertGetId($row);
            $created = DB::table('media')->where('id', $id)->first();

            // ✅ Activity Log (DocumentType-style)
            $this->logActivity(
                $request,
                'store',
                'Media',
                "Media uploaded \"".((string)($created->title ?? $fname))."\"",
                'media',
                (int)$id,
                array_keys($row),
                null,
                $created ? (array)$created : null
            );

            // ✅ Notification
            $this->persistNotification([
                'title'     => 'Media uploaded',
                'message'   => "New media uploaded: \"".((string)($created->title ?? $fname))."\"",
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'   => 'uploaded',
                    'media_id' => (int)$id,
                    'media'    => $created ? (array)$created : ['id'=>$id,'uuid'=>$uuid,'url'=>$absUrl],
                    'actor'    => $this->actor($request),
                ],
                'type'      => 'media',
                'link_url'  => $this->appUrl().'/AllMedia/'.$fname,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json(['success' => true, 'data' => $created], 201);

        } catch (\Throwable $e) {
            try { if (File::exists($fpath)) File::delete($fpath); } catch (\Throwable $e2) {}
            Log::error('media.store.db_fail', ['e' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to save media'], 500);
        }
    }

    /* =========================================================
     |  DELETE /api/media/{idOrUuid}
     |  ✅ ADD LOG + NOTIFICATION ONLY HERE
     * ========================================================= */
    public function destroy(Request $request, string $idOrUuid)
    {
        $actor = $this->actor($request);
        $hard  = filter_var($request->query('hard', false), FILTER_VALIDATE_BOOLEAN);

        $row = $this->findMediaRow($idOrUuid, true);
        if (!$row) {
            // optional: log attempt
            $this->logActivity($request, 'destroy', 'Media', 'Media not found (delete)', 'media', null, null, null, [
                'id_or_uuid' => $idOrUuid, 'hard' => $hard
            ]);
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        // Physical file path (from URL)
        $fpath = null;
        try {
            $path = parse_url((string)$row->url, PHP_URL_PATH) ?? '';
            if ($path !== '') $fpath = public_path(ltrim($path, '/'));
        } catch (\Throwable $e) {
            $fpath = null;
        }

        if ($hard) {
            // Hard delete (query builder → single delete)
            try {
                DB::table('media')->where('id', $row->id)->delete();
            } catch (\Throwable $e) {
                Log::error('media.hard_delete.db_fail', ['e'=>$e->getMessage(), 'id'=>$row->id]);
                return response()->json(['success' => false, 'error' => 'Failed to delete media'], 500);
            }

            // Remove file
            try { if ($fpath && File::exists($fpath)) File::delete($fpath); } catch (\Throwable $e) {}

            // ✅ Activity Log
            $this->logActivity(
                $request,
                'destroy',
                'Media',
                "Media hard deleted \"".((string)($row->title ?? $row->uuid))."\"",
                'media',
                (int)$row->id,
                ['hard'],
                (array)$row,
                null
            );

            // ✅ Notification
            $this->persistNotification([
                'title'     => 'Media deleted (hard)',
                'message'   => "Media hard deleted: \"".((string)($row->title ?? $row->uuid))."\"",
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'   => 'deleted_hard',
                    'media_id' => (int)$row->id,
                    'media'    => (array)$row,
                    'actor'    => $this->actor($request),
                ],
                'type'      => 'media',
                'link_url'  => null,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json(['success' => true, 'deleted' => 'hard', 'id' => (int)$row->id]);
        }

        // Soft delete
        DB::table('media')->where('id', $row->id)->update([
            'deleted_at' => Carbon::now(),
            'updated_by' => $actor['id'] ?: null,
            'updated_at' => Carbon::now(),
        ]);

        // ✅ Activity Log
        $this->logActivity(
            $request,
            'destroy',
            'Media',
            "Media soft deleted \"".((string)($row->title ?? $row->uuid))."\"",
            'media',
            (int)$row->id,
            ['soft'],
            (array)$row,
            null
        );

        // ✅ Notification
        $this->persistNotification([
            'title'     => 'Media deleted',
            'message'   => "Media soft deleted: \"".((string)($row->title ?? $row->uuid))."\"",
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'   => 'deleted_soft',
                'media_id' => (int)$row->id,
                'media'    => (array)$row,
                'actor'    => $this->actor($request),
            ],
            'type'      => 'media',
            'link_url'  => null,
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json(['success' => true, 'deleted' => 'soft', 'id' => (int)$row->id]);
    }
}
