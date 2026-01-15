<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserFolderController extends Controller
{
    /**
     * ✅ Helper: Insert log into DB
     */
    private function writeLog(Request $request, string $action, int|string|null $entityId, ?array $old = null, ?array $new = null): void
    {
        try {
            $actorId = optional($request->user())->id; // Sanctum/Auth user if available

            DB::table('user_data_activity_log')->insert([
                'uuid'        => (string) Str::uuid(),
                'action'      => $action,
                'entity_type' => 'user_folders',
                'entity_id'   => $entityId ? (int)$entityId : null,
                'actor_id'    => $actorId ? (int)$actorId : null,
                'old_values'  => $old ? json_encode($old) : null,
                'new_values'  => $new ? json_encode($new) : null,
                'ip'          => $request->ip(),
                'user_agent'  => substr((string)$request->userAgent(), 0, 1000),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // Optional: file log too
            Log::info("UserFolder {$action}", [
                'entity_id' => $entityId,
                'actor_id' => $actorId,
                'ip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            // Never break CRUD due to log failure
            Log::error("Activity log failed: " . $e->getMessage());
        }
    }

    /**
     * ✅ LIST all folders
     * GET /api/user-folders
     */
    public function index(Request $request)
{
    $q = DB::table('user_folders')
        ->whereNull('deleted_at')
        ->orderByDesc('id');

    // Optional filter by status
    if ($request->filled('status')) {
        $q->where('status', $request->query('status'));
    }

    /**
     * ✅ Dropdown mode:
     * If frontend sends X-dropdown: 1  => return ALL (no pagination)
     * OR ?show=all is sent            => return ALL (no pagination)
     */
    $xDropdown = strtolower(trim((string) $request->header('X-dropdown', '')));
    $isDropdown = in_array($xDropdown, ['1', 'true', 'yes'], true) || $request->query('show') === 'all';

    if ($isDropdown) {
        $folders = $q->get();

        return response()->json([
            'success' => true,
            'data'    => $folders,
            'meta'    => [
                'dropdown' => true,
                'total'    => $folders->count(),
            ],
        ]);
    }

    /**
     * ✅ Paginated mode (default)
     */
    $perPage = (int) $request->query('per_page', 20);
    $perPage = max(1, min($perPage, 100)); // clamp 1..100

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
     * GET /api/user-folders/{id}
     */
    public function show(Request $request, int $id)
    {
        $folder = DB::table('user_folders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $users = DB::table('users')
            ->select('id', 'uuid', 'name', 'email', 'user_folder_id')
            ->where('user_folder_id', $id)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'folder' => $folder,
            'assigned_users' => $users,
        ]);
    }

    /**
     * ✅ CREATE folder
     * POST /api/user-folders
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

        $actorId = optional($request->user())->id;

        DB::beginTransaction();
        try {
            $folderId = DB::table('user_folders')->insertGetId([
                'uuid'         => (string) Str::uuid(),
                'title'        => $request->title,
                'description'  => $request->description,
                'reason'       => $request->reason,
                'status'       => $request->status ?? 'active',
                'metadata'     => $request->metadata ? json_encode($request->metadata) : null,
                'created_by'   => $actorId,
                'created_at_ip'=> $request->ip(),
                'updated_at_ip'=> $request->ip(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $newRow = DB::table('user_folders')->where('id', $folderId)->first();
            $this->writeLog($request, 'create', $folderId, null, (array)$newRow);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Folder created successfully',
                'id'      => $folderId,
                'data'    => $newRow
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
     * PUT /api/user-folders/{id}
     */
    public function update(Request $request, int $id)
    {
        $folder = DB::table('user_folders')
            ->where('id', $id)
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

        $oldRow = (array) $folder;

        $payload = [
            'updated_at'    => now(),
            'updated_at_ip' => $request->ip(),
        ];

        if ($request->filled('title')) $payload['title'] = $request->title;
        if ($request->has('description')) $payload['description'] = $request->description;
        if ($request->has('reason')) $payload['reason'] = $request->reason;
        if ($request->filled('status')) $payload['status'] = $request->status;
        if ($request->has('metadata')) {
            $payload['metadata'] = $request->metadata ? json_encode($request->metadata) : null;
        }

        DB::beginTransaction();
        try {
            DB::table('user_folders')->where('id', $id)->update($payload);

            $newRow = DB::table('user_folders')->where('id', $id)->first();
            $this->writeLog($request, 'update', $id, $oldRow, (array)$newRow);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Folder updated successfully',
                'data'    => $newRow
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
     * DELETE /api/user-folders/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $folder = DB::table('user_folders')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$folder) {
            return response()->json(['success' => false, 'message' => 'Folder not found'], 404);
        }

        $oldRow = (array)$folder;

        DB::beginTransaction();
        try {
            // soft delete folder
            DB::table('user_folders')->where('id', $id)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
                'updated_at_ip' => $request->ip(),
            ]);

            // unlink users from folder (since users.user_folder_id is nullable)
            DB::table('users')
                ->where('user_folder_id', $id)
                ->update(['user_folder_id' => null]);

            $this->writeLog($request, 'delete', $id, $oldRow, null);

            DB::commit();

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
     * ✅ ASSIGN users to folder (Optional but very useful)
     * POST /api/user-folders/{id}/assign-users
     * body: { "user_ids": [1,2,3] }
     */
    public function assignUsers(Request $request, int $id)
    {
        $folder = DB::table('user_folders')
            ->where('id', $id)
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

        $userIds = $request->user_ids;

        DB::beginTransaction();
        try {
            // old mapping snapshot
            $oldUsers = DB::table('users')
                ->select('id', 'user_folder_id')
                ->whereIn('id', $userIds)
                ->get();

            DB::table('users')
                ->whereIn('id', $userIds)
                ->update(['user_folder_id' => $id]);

            $newUsers = DB::table('users')
                ->select('id', 'user_folder_id')
                ->whereIn('id', $userIds)
                ->get();

            $this->writeLog(
                $request,
                'assign_users',
                $id,
                ['users' => $oldUsers],
                ['users' => $newUsers]
            );

            DB::commit();

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
