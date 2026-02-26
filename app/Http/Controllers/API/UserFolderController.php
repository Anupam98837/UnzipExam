<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserFolderController extends Controller
{
    /* =========================
     * Actor / Notification helpers
     * ========================= */

    /** Actor (supports both request->user() and CheckRole attributes) */
    private function actor(Request $request): array
    {
        $attrRole = $request->attributes->get('auth_role');
        $attrId   = (int) ($request->attributes->get('auth_tokenable_id') ?? 0);

        $userId = 0;
        try {
            $userId = (int) (optional($request->user())->id ?? 0);
        } catch (\Throwable $e) {
            $userId = 0;
        }

        return [
            'role' => $attrRole ?: null,
            'id'   => $attrId > 0 ? $attrId : ($userId > 0 ? $userId : 0),
        ];
    }

    /** Admin receivers: all admins (id, role=admin). */
    private function adminReceivers(array $excludeIds = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeIds));
        $rows = DB::table('users')->select('id')->get();
        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r->id;
         }
        return $out;
    }

    /** DB-only notification insert (safe). */
    private function persistNotification(array $payload): void
    {
        try {
            $title     = (string)($payload['title']    ?? 'Notification');
            $message   = (string)($payload['message']  ?? '');
            $receivers = array_values(array_map(function ($x) {
                return [
                    'id'   => isset($x['id']) ? (int)$x['id'] : null,
                    'role' => (string)($x['role'] ?? 'unknown'),
                    'read' => (int)($x['read'] ?? 0),
                ];
            }, $payload['receivers'] ?? []));

            $metadata = $payload['metadata'] ?? [];
            $type     = (string)($payload['type'] ?? 'general');
            $linkUrl  = $payload['link_url'] ?? null;

            $priority = in_array(($payload['priority'] ?? 'normal'), ['low', 'normal', 'high', 'urgent'], true)
                ? $payload['priority'] : 'normal';

            $status = in_array(($payload['status'] ?? 'active'), ['active', 'archived', 'deleted'], true)
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

    /** Simple changed-fields detector for notifications */
    private function changedFields(array $old, array $new, array $ignore = []): array
    {
        $ignoreFlip = array_flip($ignore);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $out = [];

        foreach ($keys as $k) {
            if (isset($ignoreFlip[$k])) continue;

            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;

            // normalize strings to compare (esp. metadata json)
            if (is_string($ov) && is_string($nv)) {
                if (trim($ov) !== trim($nv)) $out[] = $k;
            } else {
                if ($ov !== $nv) $out[] = $k;
            }
        }

        return array_values($out);
    }

    /* =========================
     * ✅ Helper: Insert log into DB (existing schema)
     * =========================
     * NOTE: uuid column removed from insert to avoid SQL error (no migration change)
     */
    private function writeLog(Request $request, string $action, int|string|null $entityId, ?array $old = null, ?array $new = null): void
    {
        try {
            $actor = $this->actor($request);
            $actorId = $actor['id'] ?: null;

            DB::table('user_data_activity_log')->insert([
                // 'uuid'        => (string) Str::uuid(), // ❌ table doesn't have uuid column
                'action'      => $action,
                'entity_type' => 'user_folders',
                'entity_id'   => $entityId ? (int)$entityId : null,
                'actor_id'    => $actorId ? (int)$actorId : null,
                'old_values'  => $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                'new_values'  => $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                'ip'          => $request->ip(),
                'user_agent'  => substr((string)$request->userAgent(), 0, 1000),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            Log::info("UserFolder {$action}", [
                'entity_id' => $entityId,
                'actor_id'  => $actorId,
                'ip'        => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Activity log failed: " . $e->getMessage());
        }
    }

    /**
     * ✅ FIX: Resolve folder by id OR uuid (string)
     */
    private function resolveFolderId(int|string|null $id): ?int
    {
        $raw = trim((string)($id ?? ''));

        if ($raw === '' || $raw === 'null' || $raw === 'undefined') {
            return null;
        }

        // numeric
        if (ctype_digit($raw)) {
            return (int)$raw;
        }

        // uuid -> id
        $row = DB::table('user_folders')
            ->select('id')
            ->where('uuid', $raw)
            ->whereNull('deleted_at')
            ->first();

        return $row ? (int)$row->id : null;
    }

    /* =========================
     * ✅ LIST all folders
     * ========================= */
    public function index(Request $request)
    {
        $q = DB::table('user_folders')
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        $xDropdown  = strtolower(trim((string)$request->header('X-dropdown', '')));
        $isDropdown = in_array($xDropdown, ['1', 'true', 'yes'], true) || $request->query('show') === 'all';

        if ($isDropdown) {
            $folders = $q->select('id', 'uuid', 'title', 'status')->get();

            return response()->json([
                'success' => true,
                'data'    => $folders,
                'meta'    => [
                    'dropdown' => true,
                    'total'    => $folders->count(),
                ],
            ]);
        }

        $perPage = (int)$request->query('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $p = $q->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $p->items(),
            'meta'    => [
                'current_page' => $p->currentPage(),
                'per_page'     => $p->perPage(),
                'total'        => $p->total(),
                'total_pages'  => $p->lastPage(),
            ],
        ]);
    }

    /**
     * ✅ SHOW one folder + assigned users
     */
    public function show(Request $request, $id)
    {
        $folderId = $this->resolveFolderId($id);

        if (!$folderId) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $folder = DB::table('user_folders')
            ->where('id', $folderId)
            ->whereNull('deleted_at')
            ->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $users = DB::table('users')
            ->select('id', 'uuid', 'name', 'email', 'user_folder_id')
            ->where('user_folder_id', $folderId)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'folder'  => $folder,
            'assigned_users' => $users,
        ]);
    }

    /**
     * ✅ CREATE folder
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'reason'      => ['nullable', 'string'],
            'status'      => ['nullable', 'in:active,inactive'],
            'metadata'    => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $actor = $this->actor($request);
        $actorId = $actor['id'] ?: null;

        DB::beginTransaction();
        try {
            $folderId = DB::table('user_folders')->insertGetId([
                'uuid'          => (string)Str::uuid(),
                'title'         => $request->title,
                'description'   => $request->description,
                'reason'        => $request->reason,
                'status'        => $request->status ?? 'active',
                'metadata'      => $request->metadata ? json_encode($request->metadata, JSON_UNESCAPED_UNICODE) : null,
                'created_by'    => $actorId,
                'created_at_ip' => $request->ip(),
                'updated_at_ip' => $request->ip(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $newRow = DB::table('user_folders')->where('id', $folderId)->first();

            // ✅ Activity log
            $this->writeLog($request, 'create', $folderId, null, (array)$newRow);

            DB::commit();

            // ✅ Notification (after commit)
            $appUrl = rtrim((string)config('app.url'), '/');
            $this->persistNotification([
                'title'     => 'User folder created',
                'message'   => 'Folder "'.(($newRow->title ?? '') ?: ('#'.$folderId)).'" was created.',
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'    => 'created',
                    'folder_id' => (int)$folderId,
                    'folder'    => $newRow ? (array)$newRow : ['id'=>$folderId],
                    'actor'     => $actor,
                ],
                'type'      => 'user_folder',
                'link_url'  => $appUrl.'/user-folders/'.$folderId,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Folder created successfully',
                'id'      => $folderId,
                'data'    => $newRow,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create folder',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ UPDATE folder
     */
    public function update(Request $request, $id)
    {
        $folderId = $this->resolveFolderId($id);

        if (!$folderId) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $folder = DB::table('user_folders')
            ->where('id', $folderId)
            ->whereNull('deleted_at')
            ->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'reason'      => ['nullable', 'string'],
            'status'      => ['nullable', 'in:active,inactive'],
            'metadata'    => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $actor = $this->actor($request);
        $oldRow = (array)$folder;

        $payload = [
            'updated_at'    => now(),
            'updated_at_ip' => $request->ip(),
        ];

        if ($request->filled('title')) $payload['title'] = $request->title;
        if ($request->has('description')) $payload['description'] = $request->description;
        if ($request->has('reason')) $payload['reason'] = $request->reason;
        if ($request->filled('status')) $payload['status'] = $request->status;

        if ($request->has('metadata')) {
            $payload['metadata'] = $request->metadata ? json_encode($request->metadata, JSON_UNESCAPED_UNICODE) : null;
        }

        DB::beginTransaction();
        try {
            DB::table('user_folders')->where('id', $folderId)->update($payload);

            $newRow = DB::table('user_folders')->where('id', $folderId)->first();

            // ✅ Activity log
            $this->writeLog($request, 'update', $folderId, $oldRow, (array)$newRow);

            DB::commit();

            // ✅ Notification (after commit)
            $changed = $this->changedFields($oldRow, (array)$newRow, [
                'updated_at', 'updated_at_ip', 'created_at', 'created_by', 'created_at_ip'
            ]);

            $appUrl = rtrim((string)config('app.url'), '/');
            $this->persistNotification([
                'title'     => 'User folder updated',
                'message'   => $changed
                    ? ('Folder "'.(($newRow->title ?? '') ?: ('#'.$folderId)).'" updated ('.implode(', ', $changed).').')
                    : ('Folder "'.(($newRow->title ?? '') ?: ('#'.$folderId)).'" updated.'),
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'    => 'updated',
                    'folder_id' => (int)$folderId,
                    'changed'   => $changed,
                    'folder'    => $newRow ? (array)$newRow : ['id'=>$folderId],
                    'actor'     => $actor,
                ],
                'type'      => 'user_folder',
                'link_url'  => $appUrl.'/user-folders/'.$folderId,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Folder updated successfully',
                'data'    => $newRow,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update folder',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ DELETE folder (Soft delete)
     */
    public function destroy(Request $request, $id)
    {
        $folderId = $this->resolveFolderId($id);

        if (!$folderId) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $folder = DB::table('user_folders')
            ->where('id', $folderId)
            ->whereNull('deleted_at')
            ->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $actor  = $this->actor($request);
        $oldRow = (array)$folder;

        DB::beginTransaction();
        try {
            DB::table('user_folders')->where('id', $folderId)->update([
                'deleted_at'    => now(),
                'updated_at'    => now(),
                'updated_at_ip' => $request->ip(),
            ]);

            DB::table('users')
                ->where('user_folder_id', $folderId)
                ->update(['user_folder_id' => null]);

            // ✅ Activity log
            $this->writeLog($request, 'delete', $folderId, $oldRow, null);

            DB::commit();

            // ✅ Notification (after commit)
            $this->persistNotification([
                'title'     => 'User folder deleted',
                'message'   => 'Folder "'.(($folder->title ?? '') ?: ('#'.$folderId)).'" was deleted.',
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'    => 'deleted',
                    'folder_id' => (int)$folderId,
                    'folder'    => $oldRow,
                    'actor'     => $actor,
                ],
                'type'      => 'user_folder',
                'link_url'  => null,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Folder deleted successfully',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete folder',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ✅ ASSIGN users to folder
     * POST /api/user-folders/{id OR uuid}/assign-users
     * body: { "user_ids": [1,2,3] }
     */
    public function assignUsers(Request $request, $id)
    {
        $folderId = $this->resolveFolderId($id);

        if (!$folderId) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $folder = DB::table('user_folders')
            ->where('id', $folderId)
            ->whereNull('deleted_at')
            ->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $actor   = $this->actor($request);
        $userIds = $request->user_ids;

        DB::beginTransaction();
        try {
            $oldUsers = DB::table('users')
                ->select('id', 'user_folder_id')
                ->whereIn('id', $userIds)
                ->get()
                ->map(fn($u) => ['id' => (int)$u->id, 'user_folder_id' => $u->user_folder_id ? (int)$u->user_folder_id : null])
                ->values()
                ->all();

            DB::table('users')
                ->whereIn('id', $userIds)
                ->update(['user_folder_id' => $folderId]);

            $newUsers = DB::table('users')
                ->select('id', 'user_folder_id')
                ->whereIn('id', $userIds)
                ->get()
                ->map(fn($u) => ['id' => (int)$u->id, 'user_folder_id' => $u->user_folder_id ? (int)$u->user_folder_id : null])
                ->values()
                ->all();

            // ✅ Activity log
            $this->writeLog(
                $request,
                'assign_users',
                $folderId,
                ['users' => $oldUsers],
                ['users' => $newUsers]
            );

            DB::commit();

            // ✅ Notification (after commit)
            $appUrl = rtrim((string)config('app.url'), '/');
            $this->persistNotification([
                'title'     => 'Users assigned to folder',
                'message'   => count($userIds).' user(s) assigned to folder "'.(($folder->title ?? '') ?: ('#'.$folderId)).'".',
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'    => 'assign_users',
                    'folder_id' => (int)$folderId,
                    'folder'    => (array)$folder,
                    'user_ids'  => array_values(array_map('intval', $userIds)),
                    'actor'     => $actor,
                ],
                'type'      => 'user_folder',
                'link_url'  => $appUrl.'/user-folders/'.$folderId,
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Users assigned to folder successfully',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign users',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
