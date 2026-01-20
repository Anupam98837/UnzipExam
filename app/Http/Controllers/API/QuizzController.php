<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class QuizzController extends Controller
{
    /* =========================
     * Auth/Role helpers
     * ========================= */
    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    private function requireRole(Request $r, array $allowed)
    {
        $a = $this->actor($r);
        if (!$a['role'] || !in_array($a['role'], $allowed, true)) {
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }
        return null;
    }

    private function logWithActor(string $msg, Request $r, array $extra = []): void
    {
        $a = $this->actor($r);
        Log::info($msg, array_merge([
            'actor_role' => $a['role'],
            'actor_type' => $a['type'],
            'actor_id'   => $a['id'],
        ], $extra));
    }

    /* =========================
     * Activity Log
     * ========================= */
    private function logActivity(
        Request $request,
        string $activity, // store | update | destroy | status | restore | force
        string $note,
        string $tableName,
        ?int $recordId = null,
        ?array $changedFields = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $a = $this->actor($request);

        try {
            DB::table('user_data_activity_log')->insert([
                'performed_by'       => $a['id'] ?: 0,
                'performed_by_role'  => $a['role'] ?: null,
                'ip'                 => $request->ip(),
                'user_agent'         => (string) $request->userAgent(),
                'activity'           => $activity,
                'module'             => 'Quizz',
                'table_name'         => $tableName,
                'record_id'          => $recordId,
                'changed_fields'     => $changedFields ? json_encode(array_values($changedFields), JSON_UNESCAPED_UNICODE) : null,
                'old_values'         => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values'         => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'log_note'           => $note,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[Quizz] user_data_activity_log insert failed', ['error' => $e->getMessage()]);
        }
    }

    /* =========================
     * Notifications (DB-only)
     * ========================= */
    private function persistNotification(array $payload): void
    {
        $title     = (string)($payload['title'] ?? 'Notification');
        $message   = (string)($payload['message'] ?? '');
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

        try {
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
            Log::error('[Quizz] notifications insert failed', ['error' => $e->getMessage()]);
        }
    }

    private function adminReceivers(array $excludeIds = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeIds));

        $rows = DB::table('users')
            ->select('id', 'role', 'status')
            ->whereNull('deleted_at')
            ->whereIn('role', ['admin','super_admin'])
            ->where('status', '=', 'active')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r->id;
            if (isset($exclude[$id])) continue;

            $role = in_array($r->role, ['admin','super_admin'], true) ? $r->role : 'admin';
            $out[] = ['id' => $id, 'role' => $role, 'read' => 0];
        }
        return $out;
    }

    /* =========================
     * Helpers
     * ========================= */
    private function ensureImageFolder(): string
    {
        $destDir = public_path('assets/images/quizz');
        File::ensureDirectoryExists($destDir, 0755, true);
        return $destDir;
    }

    private function findByKey(string $key)
    {
        $q = DB::table('quizz')->whereNull('deleted_at');
        if (ctype_digit($key)) $q->where('id', (int)$key); else $q->where('uuid', $key);
        return $q->first();
    }

    private function findAnyByKey(string $key)
    {
        $q = DB::table('quizz'); // includes soft-deleted
        if (ctype_digit($key)) $q->where('id', (int)$key); else $q->where('uuid', $key);
        return $q->first();
    }

    /* =========================
     * CREATE (POST /api/quizz)
     * ========================= */
    public function store(Request $request)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;
        $this->logWithActor('[Quizz Store] begin', $request);

        $data = $request->validate([
            'quiz_name'            => ['required','string','max:255'],
            'quiz_description'     => ['sometimes','nullable','string'],
            'instructions'         => ['sometimes','nullable','string'],
            'note'                 => ['sometimes','nullable','string'],
            'is_public'            => ['sometimes','string','in:yes,no'],
            'result_set_up_type'   => ['sometimes','string', Rule::in(['Immediately','Now','Schedule'])],
            'result_release_date'  => ['sometimes','nullable','date'],
            'total_time'           => ['sometimes','nullable','integer','min:1'],
            'total_attempts'       => ['sometimes','nullable','integer','min:1'],

            // image via file OR URL
            'quiz_img'             => ['sometimes','file','image','mimes:jpeg,png,jpg,gif,webp,avif','max:4096'],
            'quiz_img_url'         => ['sometimes','nullable','url'],

            // metadata
            'metadata'             => ['sometimes','nullable','array'],

            // lifecycle
            'status'               => ['sometimes', Rule::in(['active','archived'])],
        ]);

        // Determine image path
        $imgPath = null;
        if (!empty($data['quiz_img_url'])) {
            $imgPath = (string)$data['quiz_img_url'];
        } elseif ($request->hasFile('quiz_img')) {
            $destDir = $this->ensureImageFolder();
            $ext     = strtolower($request->file('quiz_img')->getClientOriginalExtension() ?: 'jpg');
            $fname   = 'quizz_' . time() . '_' . Str::random(6) . '.' . $ext;
            $request->file('quiz_img')->move($destDir, $fname);
            $imgPath = 'assets/images/quizz/' . $fname;
        }

        $a    = $this->actor($request);
        $now  = now();
        $uuid = (string) Str::uuid();

        $insert = [
            'uuid'                 => $uuid,
            'created_by'           => $a['id'] ?: null,
            'quiz_name'            => $data['quiz_name'],
            'quiz_description'     => $data['quiz_description'] ?? null,
            'quiz_img'             => $imgPath,
            'instructions'         => $data['instructions'] ?? null,
            'note'                 => $data['note'] ?? null,
            'is_public'            => $data['is_public'] ?? 'no',
            'result_set_up_type'   => $data['result_set_up_type'] ?? 'Immediately',
            'result_release_date'  => $data['result_release_date'] ?? null,
            'total_time'           => $data['total_time'] ?? null,
            'total_attempts'       => $data['total_attempts'] ?? 1,
            'status'               => $data['status'] ?? 'active',
            'created_at'           => $now,
            'created_at_ip'        => $request->ip(),
            'updated_at'           => $now,
            'deleted_at'           => null,
            'metadata'             => isset($data['metadata'])
                                        ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE)
                                        : json_encode(new \stdClass()),
        ];

        $id = DB::table('quizz')->insertGetId($insert);
        $fresh = DB::table('quizz')->where('id', $id)->first();

        // Enrich UI counts (best-effort)
        if ($fresh) {
            try {
                $fresh->question_count = DB::table('quizz_questions')
                    ->where('quiz_id', $fresh->id)->count();
            } catch (\Throwable $e) {
                $fresh->question_count = 0;
            }
            try {
                $fresh->student_count  = DB::table('quizz_results')
                    ->where('quiz_id', $fresh->id)
                    ->distinct('user_id')->count('user_id');
            } catch (\Throwable $e) {
                $fresh->student_count = 0;
            }
        }

        // Activity + Notification
        $this->logActivity(
            $request,
            'store',
            'Created quiz "'.$insert['quiz_name'].'"',
            'quizz',
            $id,
            array_keys($insert),
            null,
            $fresh ? (array)$fresh : null
        );

        $link = rtrim((string) config('app.url'), '/') . '/admin/quizz/'.$id;
        $this->persistNotification([
            'title'     => 'Quiz created',
            'message'   => '“'.$insert['quiz_name'].'” has been created.',
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action' => 'created',
                'quiz'   => [
                    'id'     => $id,
                    'uuid'   => $uuid,
                    'name'   => $insert['quiz_name'],
                    'status' => $insert['status'],
                ],
                'created_by' => $a,
            ],
            'type'      => 'quizz',
            'link_url'  => $link,
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        $this->logWithActor('[Quizz Store] success', $request, ['quiz_id' => $id, 'uuid' => $uuid]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Quiz created successfully',
            'data'    => $fresh,
        ], 201);
    }

    /* =========================
     * LIST (GET /api/quizz)
     * ========================= */
public function index(Request $r)
{
    if ($resp = $this->requireRole($r, ['examiner','admin','super_admin'])) return $resp;

    // NEW: actor details
    $actor  = $this->actor($r);
    $role   = $actor['role'] ?? null;
    $userId = (int) ($actor['id'] ?? 0);

    $page        = max(1, (int)$r->query('page', 1));
    $perPage     = max(1, min(100, (int)$r->query('per_page', 20)));
    $qText       = trim((string)$r->query('q', ''));
    $status      = $r->query('status');             // active|archived
    $isPub       = $r->query('is_public');          // yes|no
    $sort        = (string)$r->query('sort', '-created_at'); // created_at|quiz_name|status|...
    $onlyDeleted = (int)$r->query('only_deleted', 0);

    // Base query with alias
    $q = DB::table('quizz as q');

    // NEW: if examiner, restrict to quizzes assigned to this examiner
    if ($role === 'examiner' && $userId > 0) {
        $q->join('user_quiz_assignments as uqa', function ($join) use ($userId) {
            $join->on('uqa.quiz_id', '=', 'q.id')
                 ->where('uqa.user_id', '=', $userId)
                 ->whereNull('uqa.deleted_at')
                 ->where('uqa.status', '=', 'active');
        });
    }

    if ($onlyDeleted) {
        $q->whereNotNull('q.deleted_at');
    } else {
        $q->whereNull('q.deleted_at');
    }

    if ($qText !== '') {
        $q->where(function($w) use ($qText){
            $w->where('q.quiz_name','like',"%$qText%")
              ->orWhere('q.quiz_description','like',"%$qText%");
        });
    }
    if ($status) $q->where('q.status', $status);
    if ($isPub)  $q->where('q.is_public', $isPub);

    $dir = 'asc'; $col = $sort;
    if (str_starts_with($sort, '-')) {
        $dir = 'desc';
        $col = ltrim($sort, '-');
    }
    if (!in_array($col, ['created_at','quiz_name','status','is_public','total_time'], true)) {
        $col='created_at'; $dir='desc';
    }

    // Use aliased column for count + sort
    $total = (clone $q)->count('q.id');

    $rows  = $q->orderBy('q.' . $col, $dir)
        ->offset(($page-1)*$perPage)
        ->limit($perPage)
        ->get();

    foreach ($rows as $row) {
        try {
            $row->question_count = DB::table('quizz_questions')
                ->where('quiz_id', $row->id)->count();
        } catch (\Throwable $e) {
            $row->question_count = 0;
        }
        try {
            $row->student_count  = DB::table('quizz_results')
                ->where('quiz_id', $row->id)
                ->distinct('user_id')->count('user_id');
        } catch (\Throwable $e) {
            $row->student_count = 0;
        }
    }

    return response()->json([
        'data' => $rows,
        'pagination' => [
            'page'       => $page,
            'per_page'   => $perPage,
            'total'      => $total,
            'total_pages'=> (int) ceil($total / $perPage),
        ],
    ]);
}


    /* =========================
     * SHOW (GET /api/quizz/{id|uuid})
     * ========================= */
    public function show(Request $r, string $key)
    {
        if ($resp = $this->requireRole($r, ['examiner','admin','super_admin'])) return $resp;

        $row = $this->findByKey($key);
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);

        try {
            $row->question_count = DB::table('quizz_questions')
                ->where('quiz_id', $row->id)->count();
        } catch (\Throwable $e) {
            $row->question_count = 0;
        }
        try {
            $row->student_count  = DB::table('quizz_results')
                ->where('quiz_id', $row->id)
                ->distinct('user_id')->count('user_id');
        } catch (\Throwable $e) {
            $row->student_count = 0;
        }

        return response()->json(['data'=>$row]);
    }

    /* =========================
     * UPDATE (PUT/PATCH /api/quizz/{id|uuid})
     * ========================= */
    public function update(Request $request, string $key)
    {
        // If you want examiner to be able to randomize, include 'examiner' here.
        // If NOT, keep only admin/super_admin.
        if ($resp = $this->requireRole($request, ['admin','super_admin','examiner'])) return $resp;
    
        $rowQ = DB::table('quizz')->whereNull('deleted_at');
        if (ctype_digit($key)) $rowQ->where('id',(int)$key); else $rowQ->where('uuid',$key);
        $row = $rowQ->first();
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);
    
        $id = (int)$row->id;
    
        $data = $request->validate([
            'quiz_name'            => ['sometimes','string','max:255'],
            'quiz_description'     => ['sometimes','nullable','string'],
            'instructions'         => ['sometimes','nullable','string'],
            'note'                 => ['sometimes','nullable','string'],
            'is_public'            => ['sometimes','string', Rule::in(['yes','no'])],
            'result_set_up_type'   => ['sometimes','string', Rule::in(['Immediately','Now','Schedule'])],
            'result_release_date'  => ['sometimes','nullable','date'],
            'total_time'           => ['sometimes','nullable','integer','min:1'],
            'total_attempts'       => ['sometimes','nullable','integer','min:1'],
            'status'               => ['sometimes', Rule::in(['active','archived'])],
    
            // ✅ FIX: allow randomization flags
            'is_question_random'   => ['sometimes','string', Rule::in(['yes','no'])],
            'is_option_random'     => ['sometimes','string', Rule::in(['yes','no'])],
    
            // image via file OR URL
            'quiz_img'             => ['sometimes','file','image','mimes:jpeg,png,jpg,gif,webp,avif','max:4096'],
            'quiz_img_url'         => ['sometimes','nullable','url'],
    
            'metadata'             => ['sometimes','nullable','array'],
        ]);
    
        $upd = [];
        foreach ($data as $k => $v) {
            if (in_array($k, ['quiz_img','quiz_img_url'], true)) continue; // handled below
    
            if ($k === 'metadata') {
                $v = $v !== null
                    ? json_encode($v, JSON_UNESCAPED_UNICODE)
                    : json_encode(new \stdClass());
            }
    
            $upd[$k] = $v;
        }
    
        // image update precedence: URL > File
        if (!empty($data['quiz_img_url'])) {
            $upd['quiz_img'] = (string)$data['quiz_img_url'];
        } elseif ($request->hasFile('quiz_img')) {
            $destDir = $this->ensureImageFolder();
            $ext     = strtolower($request->file('quiz_img')->getClientOriginalExtension() ?: 'jpg');
            $fname   = 'quizz_' . time() . '_' . Str::random(6) . '.' . $ext;
            $request->file('quiz_img')->move($destDir, $fname);
            $upd['quiz_img'] = 'assets/images/quizz/' . $fname;
        }
    
        if (empty($upd)) {
            return response()->json(['status'=>'noop','message'=>'Nothing to update'], 200);
        }
    
        $upd['updated_at'] = now();
        DB::table('quizz')->where('id',$id)->update($upd);
    
        $fresh = DB::table('quizz')->where('id',$id)->first();
    
        // optional enrich counts
        if ($fresh) {
            try {
                $fresh->question_count = DB::table('quizz_questions')->where('quiz_id', $fresh->id)->count();
            } catch (\Throwable $e) { $fresh->question_count = 0; }
    
            try {
                $fresh->student_count = DB::table('quizz_results')->where('quiz_id', $fresh->id)->distinct('user_id')->count('user_id');
            } catch (\Throwable $e) { $fresh->student_count = 0; }
        }
    
        $this->logActivity(
            $request,
            'update',
            'Updated quiz "'.($fresh->quiz_name ?? $row->quiz_name).'"',
            'quizz',
            $id,
            array_keys($upd),
            (array)$row,
            $fresh ? (array)$fresh : null
        );
    
        return response()->json([
            'status'=>'success',
            'message'=>'Quiz updated',
            'data'=>$fresh,
        ]);
    }
    

    /* =========================
     * STATUS (PATCH /api/quizz/{id|uuid}/status)
     * ========================= */
    public function updateStatus(Request $request, string $key)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

        $row = $this->findByKey($key);
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(['active','archived'])],
        ]);

        DB::table('quizz')->where('id', $row->id)->update([
            'status'     => $data['status'],
            'updated_at' => now(),
        ]);

        $this->logActivity(
            $request,
            'status',
            'Changed quiz status to '.$data['status'],
            'quizz',
            (int)$row->id,
            ['status'],
            (array)$row,
            null
        );

        return response()->json(['status'=>'success','message'=>'Status updated']);
    }

    /* =========================
     * DELETE (soft) (DELETE /api/quizz/{id|uuid})
     * ========================= */
    public function destroy(Request $request, string $key)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

        $row = $this->findByKey($key);
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);

        $before = (array)$row;

        DB::table('quizz')->where('id', $row->id)->update([
            'status'     => 'archived',
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logActivity(
            $request,
            'destroy',
            'Archived/Deleted quiz "'.$row->quiz_name.'"',
            'quizz',
            (int)$row->id,
            ['status','deleted_at'],
            $before,
            null
        );

        return response()->json(['status'=>'success','message'=>'Quiz archived']);
    }

    /* =========================
     * RESTORE (PATCH /api/quizz/{id|uuid}/restore)
     * ========================= */
    public function restore(Request $request, string $key)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

        $row = $this->findAnyByKey($key); // include soft-deleted
        if (!$row) return response()->json(['error' => 'Quiz not found'], 404);

        if ($row->deleted_at === null) {
            return response()->json(['status'=>'noop','message'=>'Quiz is not deleted'], 409);
        }

        $data = $request->validate([
            'status' => ['sometimes', Rule::in(['active','archived'])],
        ]);
        $newStatus = $data['status'] ?? 'active';

        DB::table('quizz')->where('id', $row->id)->update([
            'deleted_at' => null,
            'status'     => $newStatus,
            'updated_at' => now(),
        ]);

        $fresh = DB::table('quizz')->where('id', $row->id)->first();

        $this->logActivity(
            $request,
            'restore',
            'Restored quiz "'.($fresh->quiz_name ?? 'N/A').'"',
            'quizz',
            (int)$row->id,
            ['deleted_at','status'],
            (array)$row,
            $fresh ? (array)$fresh : null
        );

        $this->persistNotification([
            'title'     => 'Quiz restored',
            'message'   => '“'.($fresh->quiz_name ?? 'Quiz').'” has been restored.',
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action' => 'restored',
                'quiz'   => [
                    'id'     => (int)$row->id,
                    'uuid'   => $row->uuid ?? null,
                    'status' => $newStatus,
                ],
                'restored_by' => $this->actor($request),
            ],
            'type'      => 'quizz',
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Quiz restored',
            'data'    => $fresh,
        ]);
    }

    /* =========================
     * FORCE DELETE (DELETE /api/quizz/{id|uuid}/force)
     * ========================= */
    public function forceDelete(Request $request, string $key)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

        $row = $this->findAnyByKey($key);
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);

        $before = (array)$row;

        // Hard delete record (FKs on quizz_questions will cascade)
        DB::table('quizz')->where('id', $row->id)->delete();

        // Optional: cascade clean-ups (best-effort)
        try { DB::table('quizz_results')->where('quiz_id', $row->id)->delete(); } catch (\Throwable $e) {}
        try { DB::table('quizz_notes')->where('quiz_id', $row->id)->delete(); } catch (\Throwable $e) {}

        $this->logActivity(
            $request,
            'force',
            'Permanently deleted quiz "'.($row->quiz_name ?? 'N/A').'"',
            'quizz',
            (int)$row->id,
            null,
            $before,
            null
        );

        return response()->json(['status'=>'success','message'=>'Quiz permanently deleted']);
    }

    /* =========================
     * NOTES
     *  - GET  /api/quizz/{key}/notes
     *  - POST /api/quizz/{key}/notes {note:string}
     * ========================= */
    public function listNotes(Request $request, string $key)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

        $row = $this->findAnyByKey($key);
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);

        $notes = DB::table('quizz_notes')
            ->where('quiz_id', $row->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status'=>'success','data'=>$notes]);
    }

    public function addNote(Request $request, string $key)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) return $resp;

        $row = $this->findAnyByKey($key);
        if (!$row) return response()->json(['error'=>'Quiz not found'], 404);

        $data = $request->validate([
            'note' => ['required','string'],
        ]);

        $a = $this->actor($request);

        $id = DB::table('quizz_notes')->insertGetId([
            'quiz_id'          => (int)$row->id,
            'note'             => $data['note'],
            'created_by'       => $a['id'] ?: null,
            'created_by_role'  => $a['role'] ?: null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->logActivity(
            $request,
            'update',
            'Added note to quiz "'.($row->quiz_name ?? 'N/A').'"',
            'quizz',
            (int)$row->id,
            ['note'],
            null,
            ['note'=>$data['note']]
        );

        $note = DB::table('quizz_notes')->where('id',$id)->first();

        return response()->json(['status'=>'success','message'=>'Note added','data'=>$note]);
    }

    /**
     * DELETED INDEX (GET /api/quizz/deleted)
     * Lists soft-deleted quizzes (?q=search, ?per_page=, ?page=)
     */
    public function deletedIndex(Request $r)
    {
        if ($resp = $this->requireRole($r, ['admin','super_admin'])) return $resp;

        $page    = max(1, (int)$r->query('page', 1));
        $perPage = max(1, min(200, (int)$r->query('per_page', 20)));
        $qText   = trim((string)$r->query('q', ''));

        // Base query: soft-deleted quizzes
        $q = DB::table('quizz as q')
            ->leftJoin('users as creator', 'creator.id', '=', 'q.created_by')
            ->whereNotNull('q.deleted_at');

        // Optional text search
        if ($qText !== '') {
            $q->where(function($w) use ($qText) {
                $w->where('q.quiz_name', 'like', "%{$qText}%")
                  ->orWhere('q.quiz_description', 'like', "%{$qText}%")
                  ->orWhere('q.uuid', 'like', "%{$qText}%");
            });
        }

        // Select fields; include counts via subqueries (avoids N+1)
        $select = [
            'q.id',
            'q.uuid',
            'q.quiz_name as title',
            'q.quiz_description as excerpt',
            'q.quiz_img',
            'q.total_time',
            'q.total_questions',
            'q.total_attempts',
            'q.is_public',
            'q.result_set_up_type',
            'q.status',
            'q.created_by',
            'creator.name as created_by_name',
            'q.deleted_at',
            DB::raw('(SELECT COUNT(*) FROM quizz_questions qq WHERE qq.quiz_id = q.id) AS question_count'),
            DB::raw('(SELECT COUNT(DISTINCT user_id) FROM quizz_results qr WHERE qr.quiz_id = q.id) AS student_count'),
        ];

        $query = $q->select($select)
                   ->orderBy('q.deleted_at', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($row) {
            return [
                'id'               => (int) $row->id,
                'uuid'             => $row->uuid,
                'title'            => $row->title,
                'excerpt'          => $row->excerpt,
                'quiz_img'         => $row->quiz_img,
                'total_time'       => $row->total_time !== null ? (int)$row->total_time : null,
                'total_questions'  => $row->total_questions !== null ? (int)$row->total_questions : null,
                'total_attempts'   => $row->total_attempts !== null ? (int)$row->total_attempts : null,
                'is_public'        => $row->is_public === 'yes' || $row->is_public === 1 || $row->is_public === true,
                'result_set_up_type' => $row->result_set_up_type,
                'status'           => $row->status,
                'created_by'       => $row->created_by ? (int)$row->created_by : null,
                'created_by_name'  => $row->created_by_name ?? null,
                'deleted_at'       => $row->deleted_at
                    ? \Carbon\Carbon::parse($row->deleted_at)->toDateTimeString()
                    : null,
                'question_count'   => isset($row->question_count) ? (int)$row->question_count : 0,
                'student_count'    => isset($row->student_count) ? (int)$row->student_count : 0,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'pagination' => [
                'total'        => (int) $paginator->total(),
                'per_page'     => (int) $paginator->perPage(),
                'current_page' => (int) $paginator->currentPage(),
                'last_page'    => (int) $paginator->lastPage(),
            ],
        ]);
    }

    /* =========================
     * MY QUIZZES (student)
     *  GET /api/quizz/my
     *  - Lists quizzes visible to the logged-in student
     *  - Includes latest attempt + result for that student
     * ========================= */
public function myQuizzes(Request $r)
{
    // Allow student, but also let admin/super_admin hit this if needed
    if ($resp = $this->requireRole($r, ['student','admin','super_admin'])) return $resp;

    $actor  = $this->actor($r);
    $role   = (string) ($actor['role'] ?? '');
    $userId = (int) ($actor['id'] ?? 0);

    if (!$userId) {
        return response()->json(['error' => 'Unable to resolve user from token'], 403);
    }

    // ✅ for admin/super_admin: allow viewing assigned quizzes for a specific student using ?user_id=
    $targetUserId = $userId;
    if (in_array($role, ['admin', 'super_admin'], true)) {
        $requestedUserId = (int) $r->query('user_id', 0);
        if ($requestedUserId > 0) {
            $targetUserId = $requestedUserId;
        }
    }

    $page    = max(1, (int) $r->query('page', 1));
    $perPage = max(1, min(50, (int) $r->query('per_page', 12)));
    $search  = trim((string) $r->query('q', ''));

    /* ============================================================
     | ✅ Only ASSIGNED quizzes for this user
     |============================================================ */

    $assignedSub = DB::table('user_quiz_assignments as uqa')
        ->select('uqa.quiz_id')
        ->where('uqa.user_id', $targetUserId)
        ->where('uqa.status', 'active')
        ->whereNull('uqa.deleted_at')
        ->distinct();

    // ---- Subquery: latest attempt per quiz for this user ----
    $attemptSub = DB::table('quizz_attempts')
        ->select('quiz_id', DB::raw('MAX(id) as latest_id'))
        ->where('user_id', $targetUserId)
        ->groupBy('quiz_id');

    // ---- Subquery: latest result per quiz for this user ----
    $resultSub = DB::table('quizz_results')
        ->select('quiz_id', DB::raw('MAX(id) as latest_id'))
        ->where('user_id', $targetUserId)
        ->groupBy('quiz_id');

    $q = DB::table('quizz as q')
        // ✅ ONLY quizzes that are assigned to this user
        ->joinSub($assignedSub, 'asq', function ($join) {
            $join->on('asq.quiz_id', '=', 'q.id');
        })

        // ✅ join assignment row (to show code/date if needed)
        ->join('user_quiz_assignments as uqa', function ($join) use ($targetUserId) {
            $join->on('uqa.quiz_id', '=', 'q.id')
                 ->where('uqa.user_id', '=', $targetUserId)
                 ->where('uqa.status', '=', 'active')
                 ->whereNull('uqa.deleted_at');
        })

        // join latest attempt
        ->leftJoinSub($attemptSub, 'la', function ($join) {
            $join->on('la.quiz_id', '=', 'q.id');
        })
        ->leftJoin('quizz_attempts as qa', 'qa.id', '=', 'la.latest_id')

        // join latest result
        ->leftJoinSub($resultSub, 'lr', function ($join) {
            $join->on('lr.quiz_id', '=', 'q.id');
        })
        ->leftJoin('quizz_results as qr', 'qr.id', '=', 'lr.latest_id')

        // only active, non-deleted quizzes
        ->whereNull('q.deleted_at')
        ->where('q.status', '=', 'active');

    // Optional text search
    if ($search !== '') {
        $q->where(function ($w) use ($search) {
            $w->where('q.quiz_name', 'like', "%{$search}%")
              ->orWhere('q.quiz_description', 'like', "%{$search}%");
        });
    }

    $select = [
        'q.id',
        'q.uuid',
        'q.quiz_name',
        'q.quiz_description',
        'q.quiz_img',
        'q.total_time',
        'q.total_questions',
        'q.total_attempts',
        'q.is_public',
        'q.result_set_up_type',
        'q.status',
        'q.created_at',

        // ✅ assignment details
        'uqa.id              as assignment_id',
        'uqa.uuid            as assignment_uuid',
        'uqa.assignment_code as assignment_code',
        'uqa.assigned_by     as assigned_by',
        'uqa.assigned_at     as assigned_at',

        // latest attempt (for this user)
        'qa.id           as attempt_id',
        'qa.status       as attempt_status',
        'qa.started_at   as attempt_started_at',
        'qa.submitted_at as attempt_submitted_at',

        // latest result (for this user)
        'qr.id           as result_id',
        'qr.created_at   as result_created_at',
    ];

    $paginator = $q->select($select)
        ->orderBy('uqa.assigned_at', 'desc')   // ✅ better ordering = recently assigned first
        ->orderBy('q.created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    $items = collect($paginator->items())->map(function ($row) {
        // derive a simple status for the UI: upcoming | in_progress | completed
        $myStatus = 'upcoming';

        if ($row->attempt_status === 'in_progress' || $row->attempt_status === 'started') {
            $myStatus = 'in_progress';
        } elseif ($row->result_id ||
            in_array($row->attempt_status, ['submitted','finished','completed','graded'], true)) {
            $myStatus = 'completed';
        } elseif ($row->attempt_id) {
            $myStatus = 'in_progress';
        }

        return [
            'id'              => (int) $row->id,
            'uuid'            => $row->uuid,
            'title'           => $row->quiz_name,
            'excerpt'         => $row->quiz_description,
            'quiz_img'        => $row->quiz_img,
            'total_time'      => $row->total_time !== null ? (int) $row->total_time : null,
            'total_questions' => $row->total_questions !== null ? (int) $row->total_questions : null,
            'total_attempts'  => $row->total_attempts !== null ? (int) $row->total_attempts : null,
            'is_public'       => $row->is_public === 'yes' || $row->is_public === 1 || $row->is_public === true,
            'result_set_up_type' => $row->result_set_up_type,
            'status'          => $row->status,
            'created_at'      => $row->created_at
                ? \Carbon\Carbon::parse($row->created_at)->toDateTimeString()
                : null,

            // ✅ assignment info
            'assignment' => [
                'id'              => (int) $row->assignment_id,
                'uuid'            => $row->assignment_uuid,
                'assignment_code' => $row->assignment_code,
                'assigned_by'     => $row->assigned_by ? (int) $row->assigned_by : null,
                'assigned_at'     => $row->assigned_at
                    ? \Carbon\Carbon::parse($row->assigned_at)->toDateTimeString()
                    : null,
            ],

            // computed status from this student's perspective
            'my_status' => $myStatus, // upcoming | in_progress | completed

            'attempt' => $row->attempt_id ? [
                'id'           => (int) $row->attempt_id,
                'status'       => $row->attempt_status,
                'started_at'   => $row->attempt_started_at,
                'submitted_at' => $row->attempt_submitted_at,
            ] : null,

            'result'  => $row->result_id ? [
                'id'         => (int) $row->result_id,
                'created_at' => $row->result_created_at
                    ? \Carbon\Carbon::parse($row->result_created_at)->toDateTimeString()
                    : null,
            ] : null,
        ];
    })->values();

    return response()->json([
        'success'    => true,
        'data'       => $items,
        'pagination' => [
            'total'        => (int) $paginator->total(),
            'per_page'     => (int) $paginator->perPage(),
            'current_page' => (int) $paginator->currentPage(),
            'last_page'    => (int) $paginator->lastPage(),
        ],
    ]);
}

}
