<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BubbleGameController extends Controller
{
    /* =========================
     * Auth/Role + Activity Log + Notifications
     * ========================= */

    /**
     * Convenience helper to read actor data attached by CheckRole.
     */
    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    /**
     * Simple role gate (same style as QuizzController).
     */
    private function requireRole(Request $request, array $allowed)
    {
        $actor = $this->actor($request);

        if (!$actor['role'] || !in_array($actor['role'], $allowed, true)) {
            return response()->json([
                'error'   => 'Unauthorized Access',
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        return null;
    }

    /**
     * Insert row into user_data_activity_log using DB facade.
     * Columns expected:
     * performed_by, performed_by_role, ip, user_agent, activity, module, table_name,
     * record_id, changed_fields (json), old_values (json), new_values (json),
     * log_note, created_at, updated_at
     */
    private function logActivity(
        Request $request,
        string $activity,                 // 'store'|'update'|'destroy'
        string $module,                   // 'BubbleGames'
        string $note,
        string $tableName,
        ?int $recordId = null,
        ?array $changed = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $a = $this->actor($request);

        $changedFields = null;
        if (is_array($changed)) {
            $changedFields = array_values(array_unique(
                array_keys($changed) === range(0, count($changed) - 1) ? $changed : array_keys($changed)
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

    /** Admin receivers: all admins (id, role=admin). */
    private function adminReceivers(array $excludeIds = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeIds));
        $rows = DB::table('users')->select('id')->get();
        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r->id;
            if (!isset($exclude[$id])) $out[] = ['id' => $id, 'role' => 'admin', 'read' => 0];
        }
        return $out;
    }

    /** DB-only notification insert */
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

            $status   = in_array(($payload['status'] ?? 'active'), ['active', 'archived', 'deleted'], true)
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

    /** Compare old vs new and return changed keys (ignore noisy keys) */
    private function changedFields(array $old, array $new, array $ignore = []): array
    {
        $ignoreFlip = array_flip($ignore);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $out = [];

        foreach ($keys as $k) {
            if (isset($ignoreFlip[$k])) continue;

            $ov = $old[$k] ?? null;
            $nv = $new[$k] ?? null;

            if (is_string($ov) && is_string($nv)) {
                if (trim($ov) !== trim($nv)) $out[] = $k;
            } else {
                if ($ov !== $nv) $out[] = $k;
            }
        }

        return array_values($out);
    }

    /** Simple link helper (adjust path if your admin URL differs) */
    private function gameLink(string $uuid): string
    {
        return rtrim((string)config('app.url'), '/') . '/bubble-games/' . $uuid;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            );

        /**
         * ✅ Bin / Deleted handling
         * - only_deleted=1 => ONLY soft-deleted rows
         * - with_deleted=1 => include both deleted + non-deleted
         * - default        => ONLY non-deleted rows
         */
        $onlyDeleted = $request->boolean('only_deleted') || $request->boolean('bin') || $request->boolean('trash');
        $withDeleted = $request->boolean('with_deleted') || $request->boolean('include_deleted');

        if ($onlyDeleted) {
            $query->whereNotNull('bubble_game.deleted_at');
        } elseif (!$withDeleted) {
            $query->whereNull('bubble_game.deleted_at');
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('bubble_game.status', $request->status);
        }

        if ($request->has('search') && trim($request->search) !== '') {
            $query->where('bubble_game.title', 'like', '%' . trim($request->search) . '%');
        }

        $allowedOrderBy = [
            'bubble_game.title',
            'bubble_game.status',
            'bubble_game.max_attempts',
            'bubble_game.per_question_time_sec',
            'bubble_game.created_at',
            'bubble_game.updated_at',
            'bubble_game.deleted_at',
        ];

        $orderBy = $request->get('order_by', 'bubble_game.created_at');
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'bubble_game.created_at';
        }

        $orderDir = strtolower($request->get('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $orderDir);

        $perPage = max(1, (int) $request->get('per_page', 15));
        $page    = max(1, (int) $request->get('page', 1));
        $offset  = ($page - 1) * $perPage;

        $total = (clone $query)->count();
        $bubbleGames = $query->offset($offset)->limit($perPage)->get();

        foreach ($bubbleGames as $game) {
            $game->metadata = $game->metadata ? json_decode($game->metadata) : null;
        }

        return response()->json([
            'success' => true,
            'data' => $bubbleGames,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total ? ($offset + 1) : 0,
                'to' => min($offset + $perPage, $total)
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * ✅ POST: added activity log + notification
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:180',
            'description' => 'nullable|string',
            'max_attempts' => 'nullable|integer|min:1',
            'per_question_time_sec' => 'nullable|integer|min:1',
            'is_question_random' => ['nullable', Rule::in(['yes', 'no'])],
            'is_bubble_positions_random' => ['nullable', Rule::in(['yes', 'no'])],
            'allow_skip' => ['nullable', Rule::in(['yes', 'no'])],
            'points_correct' => 'nullable|integer',
            'points_wrong' => 'nullable|integer',
            'show_solution_after' => ['nullable', Rule::in(['never', 'after_each', 'after_finish'])],
            'instructions_html' => 'nullable|string',
            'status' => 'nullable|string|max:20',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $actor = $this->actor($request);

        $insertData = [
            'uuid' => (string) Str::uuid(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'max_attempts' => $data['max_attempts'] ?? 1,
            'per_question_time_sec' => $data['per_question_time_sec'] ?? 30,
            'is_question_random' => $data['is_question_random'] ?? 'no',
            'is_bubble_positions_random' => $data['is_bubble_positions_random'] ?? 'yes',
            'allow_skip' => $data['allow_skip'] ?? 'no',
            'points_correct' => $data['points_correct'] ?? 1,
            'points_wrong' => $data['points_wrong'] ?? 0,
            'show_solution_after' => $data['show_solution_after'] ?? 'after_finish',
            'instructions_html' => $data['instructions_html'] ?? null,
            'status' => $data['status'] ?? 'active',
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_by' => Auth::id(),
            'created_at_ip' => $request->ip(),
            'updated_at_ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        try {
            $gameId = DB::table('bubble_game')->insertGetId($insertData);

            $bubbleGame = DB::table('bubble_game')
                ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
                ->select(
                    'bubble_game.*',
                    'users.name as creator_name',
                    'users.email as creator_email'
                )
                ->where('bubble_game.id', $gameId)
                ->first();

            if ($bubbleGame) {
                $bubbleGame->metadata = $bubbleGame->metadata ? json_decode($bubbleGame->metadata) : null;
            }

            // ✅ activity log
            $this->logActivity(
                $request,
                'store',
                'BubbleGames',
                "Created bubble game \"{$data['title']}\"",
                'bubble_game',
                (int)$gameId,
                array_keys($data),
                null,
                $bubbleGame ? (array)$bubbleGame : ['id' => $gameId, 'uuid' => $insertData['uuid'], 'title' => $data['title']]
            );

            // ✅ notify admins
            $this->persistNotification([
                'title'     => 'Bubble game created',
                'message'   => "Bubble game \"{$data['title']}\" was created.",
                'receivers' => $this->adminReceivers(),
                'metadata'  => [
                    'action'   => 'created',
                    'game_id'  => (int)$gameId,
                    'game_uuid'=> (string)$insertData['uuid'],
                    'game'     => $bubbleGame ? (array)$bubbleGame : ['id' => $gameId, 'uuid' => $insertData['uuid'], 'title' => $data['title']],
                    'actor'    => $actor,
                ],
                'type'      => 'bubble_game',
                'link_url'  => $this->gameLink((string)$insertData['uuid']),
                'priority'  => 'normal',
                'status'    => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bubble game created successfully',
                'data' => $bubbleGame
            ], 201);

        } catch (\Throwable $e) {
            Log::error('BubbleGame.store failed', ['e' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bubble game',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.uuid', $uuid)
            ->whereNull('bubble_game.deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $bubbleGame->metadata = $bubbleGame->metadata ? json_decode($bubbleGame->metadata) : null;

        return response()->json([
            'success' => true,
            'data' => $bubbleGame
        ]);
    }

    /**
     * Update the specified resource in storage.
     * ✅ PUT/PATCH: added activity log + notification
     */
    public function update(Request $request, string $uuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            // optional: log failed attempt
            $this->logActivity($request, 'update', 'BubbleGames', 'Bubble game not found', 'bubble_game', null, null, null, ['uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:180',
            'description' => 'nullable|string',
            'max_attempts' => 'nullable|integer|min:1',
            'per_question_time_sec' => 'nullable|integer|min:1',
            'is_question_random' => ['nullable', Rule::in(['yes', 'no'])],
            'is_bubble_positions_random' => ['nullable', Rule::in(['yes', 'no'])],
            'allow_skip' => ['nullable', Rule::in(['yes', 'no'])],
            'points_correct' => 'nullable|integer',
            'points_wrong' => 'nullable|integer',
            'show_solution_after' => ['nullable', Rule::in(['never', 'after_each', 'after_finish'])],
            'instructions_html' => 'nullable|string',
            'status' => 'nullable|string|max:20',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $actor = $this->actor($request);
        $oldRow = (array)$bubbleGame;

        $updateData = [
            'updated_at_ip' => $request->ip(),
            'updated_at' => now()
        ];

        if (isset($data['title'])) $updateData['title'] = $data['title'];
        if (array_key_exists('description', $data)) $updateData['description'] = $data['description'];
        if (isset($data['max_attempts'])) $updateData['max_attempts'] = $data['max_attempts'];
        if (isset($data['per_question_time_sec'])) $updateData['per_question_time_sec'] = $data['per_question_time_sec'];
        if (isset($data['is_question_random'])) $updateData['is_question_random'] = $data['is_question_random'];
        if (isset($data['is_bubble_positions_random'])) $updateData['is_bubble_positions_random'] = $data['is_bubble_positions_random'];
        if (isset($data['allow_skip'])) $updateData['allow_skip'] = $data['allow_skip'];
        if (isset($data['points_correct'])) $updateData['points_correct'] = $data['points_correct'];
        if (isset($data['points_wrong'])) $updateData['points_wrong'] = $data['points_wrong'];
        if (isset($data['show_solution_after'])) $updateData['show_solution_after'] = $data['show_solution_after'];
        if (isset($data['instructions_html'])) $updateData['instructions_html'] = $data['instructions_html'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['metadata'])) $updateData['metadata'] = json_encode($data['metadata']);

        DB::table('bubble_game')->where('uuid', $uuid)->update($updateData);

        $updatedGame = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.uuid', $uuid)
            ->first();

        if ($updatedGame) {
            $updatedGame->metadata = $updatedGame->metadata ? json_decode($updatedGame->metadata) : null;
        }

        $newRow = $updatedGame ? (array)$updatedGame : [];
        $changed = $this->changedFields(
            $oldRow,
            $newRow,
            ['updated_at','updated_at_ip','created_at','created_at_ip','created_by','deleted_at']
        );

        // ✅ activity log
        $this->logActivity(
            $request,
            'update',
            'BubbleGames',
            $changed ? ('Bubble game updated: ' . implode(', ', $changed)) : 'Bubble game updated',
            'bubble_game',
            (int)($updatedGame->id ?? $bubbleGame->id ?? 0) ?: null,
            $changed ?: array_keys($updateData),
            $oldRow,
            $newRow ?: null
        );

        // ✅ notify admins
        $this->persistNotification([
            'title'     => 'Bubble game updated',
            'message'   => $changed
                ? ("Bubble game \"{$updatedGame->title}\" updated (" . implode(', ', $changed) . ").")
                : ("Bubble game \"{$updatedGame->title}\" updated."),
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'    => 'updated',
                'game_id'   => (int)($updatedGame->id ?? $bubbleGame->id ?? 0),
                'game_uuid' => (string)$uuid,
                'changed'   => $changed,
                'game'      => $newRow ?: ['uuid' => $uuid],
                'actor'     => $actor,
            ],
            'type'      => 'bubble_game',
            'link_url'  => $this->gameLink((string)$uuid),
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game updated successfully',
            'data' => $updatedGame
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     * ✅ DELETE: added activity log + notification
     */
    public function destroy(string $uuid)
    {
        $req = request();

        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            $this->logActivity($req, 'destroy', 'BubbleGames', 'Bubble game not found', 'bubble_game', null, null, null, ['uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $oldRow = (array)$bubbleGame;

        DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->update([
                'deleted_at'    => now(),
                'updated_at'    => now(),
                'updated_at_ip' => $req->ip(),
            ]);

        // fetch fresh snapshot (optional)
        $fresh = DB::table('bubble_game')->where('uuid', $uuid)->first();

        // ✅ activity log
        $this->logActivity(
            $req,
            'destroy',
            'BubbleGames',
            "Bubble game \"{$bubbleGame->title}\" deleted (soft)",
            'bubble_game',
            (int)$bubbleGame->id,
            ['deleted_at'],
            $oldRow,
            $fresh ? (array)$fresh : null
        );

        // ✅ notify admins
        $this->persistNotification([
            'title'     => 'Bubble game deleted',
            'message'   => "Bubble game \"{$bubbleGame->title}\" was deleted (soft).",
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'    => 'deleted_soft',
                'game_id'   => (int)$bubbleGame->id,
                'game_uuid' => (string)$uuid,
                'game'      => $oldRow,
                'actor'     => $this->actor($req),
            ],
            'type'      => 'bubble_game',
            'link_url'  => null,
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game deleted successfully'
        ]);
    }

    /**
     * Restore a soft deleted resource.
     * ✅ PATCH: added activity log + notification
     */
    public function restore(string $uuid)
    {
        $req = request();

        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNotNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            $this->logActivity($req, 'update', 'BubbleGames', 'Bubble game not found or not deleted', 'bubble_game', null, null, null, ['uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found or not deleted'
            ], 404);
        }

        $oldRow = (array)$bubbleGame;

        DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->update([
                'deleted_at'    => null,
                'updated_at'    => now(),
                'updated_at_ip' => $req->ip(),
            ]);

        $restoredGame = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.uuid', $uuid)
            ->first();

        if ($restoredGame) {
            $restoredGame->metadata = $restoredGame->metadata ? json_decode($restoredGame->metadata) : null;
        }

        // ✅ activity log
        $this->logActivity(
            $req,
            'update',
            'BubbleGames',
            "Bubble game \"{$bubbleGame->title}\" restored",
            'bubble_game',
            (int)$bubbleGame->id,
            ['deleted_at'],
            $oldRow,
            $restoredGame ? (array)$restoredGame : null
        );

        // ✅ notify admins
        $this->persistNotification([
            'title'     => 'Bubble game restored',
            'message'   => "Bubble game \"{$bubbleGame->title}\" was restored.",
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'    => 'restored',
                'game_id'   => (int)$bubbleGame->id,
                'game_uuid' => (string)$uuid,
                'game'      => $restoredGame ? (array)$restoredGame : ['uuid' => $uuid],
                'actor'     => $this->actor($req),
            ],
            'type'      => 'bubble_game',
            'link_url'  => $this->gameLink((string)$uuid),
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game restored successfully',
            'data' => $restoredGame
        ]);
    }

    /**
     * Permanently delete a resource.
     * ✅ DELETE: added activity log + notification
     */
    public function forceDelete(string $uuid)
    {
        $req = request();

        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->first();

        if (!$bubbleGame) {
            $this->logActivity($req, 'destroy', 'BubbleGames', 'Bubble game not found (force delete)', 'bubble_game', null, null, null, ['uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $oldRow = (array)$bubbleGame;

        DB::table('bubble_game')->where('uuid', $uuid)->delete();

        // ✅ activity log
        $this->logActivity(
            $req,
            'destroy',
            'BubbleGames',
            "Bubble game \"{$bubbleGame->title}\" permanently deleted",
            'bubble_game',
            (int)$bubbleGame->id,
            array_keys($oldRow),
            $oldRow,
            null
        );

        // ✅ notify admins
        $this->persistNotification([
            'title'     => 'Bubble game permanently deleted',
            'message'   => "Bubble game \"{$bubbleGame->title}\" was permanently deleted.",
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'    => 'deleted_hard',
                'game_id'   => (int)$bubbleGame->id,
                'game_uuid' => (string)$uuid,
                'game'      => $oldRow,
                'actor'     => $this->actor($req),
            ],
            'type'      => 'bubble_game',
            'link_url'  => null,
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game permanently deleted'
        ]);
    }

    /**
     * Duplicate an existing bubble game.
     * ✅ POST: added activity log + notification
     */
    public function duplicate(string $uuid)
    {
        $req = request();

        $original = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$original) {
            $this->logActivity($req, 'store', 'BubbleGames', 'Bubble game not found (duplicate)', 'bubble_game', null, null, null, ['uuid' => $uuid]);
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $actor = $this->actor($req);

        $duplicateData = [
            'uuid' => (string) Str::uuid(),
            'title' => $original->title . ' (Copy)',
            'description' => $original->description,
            'max_attempts' => $original->max_attempts,
            'per_question_time_sec' => $original->per_question_time_sec,
            'is_question_random' => $original->is_question_random,
            'is_bubble_positions_random' => $original->is_bubble_positions_random,
            'allow_skip' => $original->allow_skip,
            'points_correct' => $original->points_correct,
            'points_wrong' => $original->points_wrong,
            'show_solution_after' => $original->show_solution_after,
            'instructions_html' => $original->instructions_html,
            'status' => $original->status,
            'metadata' => $original->metadata,
            'created_by' => Auth::id(),
            'created_at_ip' => $req->ip(),
            'updated_at_ip' => $req->ip(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        $duplicateId = DB::table('bubble_game')->insertGetId($duplicateData);

        $duplicate = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.id', $duplicateId)
            ->first();

        if ($duplicate) {
            $duplicate->metadata = $duplicate->metadata ? json_decode($duplicate->metadata) : null;
        }

        // ✅ activity log
        $this->logActivity(
            $req,
            'store',
            'BubbleGames',
            "Duplicated bubble game \"{$original->title}\"",
            'bubble_game',
            (int)$duplicateId,
            array_keys($duplicateData),
            (array)$original,
            $duplicate ? (array)$duplicate : ['id' => $duplicateId, 'uuid' => $duplicateData['uuid'], 'title' => $duplicateData['title']]
        );

        // ✅ notify admins
        $this->persistNotification([
            'title'     => 'Bubble game duplicated',
            'message'   => "Bubble game \"{$original->title}\" duplicated as \"{$duplicateData['title']}\".",
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'      => 'duplicated',
                'source_uuid' => (string)$uuid,
                'new_id'      => (int)$duplicateId,
                'new_uuid'    => (string)$duplicateData['uuid'],
                'game'        => $duplicate ? (array)$duplicate : ['id'=>$duplicateId,'uuid'=>$duplicateData['uuid'],'title'=>$duplicateData['title']],
                'actor'       => $actor,
            ],
            'type'      => 'bubble_game',
            'link_url'  => $this->gameLink((string)$duplicateData['uuid']),
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game duplicated successfully',
            'data' => $duplicate
        ], 201);
    }
/* =========================
 * MY BUBBLE GAMES (student)  (GET only)
 * ========================= */
public function myBubbleGames(Request $r)
{
    if ($resp = $this->requireRole($r, ['student', 'admin', 'super_admin'])) return $resp;

    $actor  = $this->actor($r);
    $userId = (int) ($actor['id'] ?? 0);
    $role   = (string) ($actor['role'] ?? '');

    if (!$userId) {
        return response()->json(['success' => false, 'message' => 'Unable to resolve user from token'], 403);
    }

    $page    = max(1, (int) $r->query('page', 1));
    $perPage = max(1, min(50, (int) $r->query('per_page', 12)));
    $search  = trim((string) $r->query('q', ''));

    // ✅ Column safety (won’t break if column doesn’t exist)
    $hasInstr        = \Schema::hasColumn('bubble_game', 'instructions');
    $hasInstrAlt     = \Schema::hasColumn('bubble_game', 'instruction');
    $hasInstrHtml    = \Schema::hasColumn('bubble_game', 'instructions_html');
    $hasInstrHtmlAlt = \Schema::hasColumn('bubble_game', 'instruction_html');

    $qCountSub = DB::table('bubble_game_questions')
        ->select('bubble_game_id', DB::raw('COUNT(*) as total_questions'))
        ->where('status', '=', 'active')
        ->groupBy('bubble_game_id');

    $resultSub = DB::table('bubble_game_results')
        ->select('bubble_game_id', DB::raw('MAX(id) as latest_id'))
        ->where('user_id', $userId)
        ->whereNull('deleted_at')
        ->groupBy('bubble_game_id');

    $attemptStatSub = DB::table('bubble_game_results')
        ->select([
            'bubble_game_id',
            DB::raw('COUNT(*) as attempts_count'),
            DB::raw('COALESCE(MAX(attempt_no), 0) as max_attempt_no'),
        ])
        ->where('user_id', $userId)
        ->whereNull('deleted_at')
        ->groupBy('bubble_game_id');

    $assignSub = DB::table('user_bubble_game_assignments as uga')
        ->select([
            'uga.bubble_game_id',
            DB::raw('MAX(uga.assigned_at) as assigned_at'),
        ])
        ->where('uga.user_id', '=', $userId)
        ->where('uga.status', '=', 'active')
        ->whereNull('uga.deleted_at')
        ->groupBy('uga.bubble_game_id');

    $q = DB::table('bubble_game as bg')
        ->leftJoinSub($qCountSub, 'qc', function ($join) {
            $join->on('qc.bubble_game_id', '=', 'bg.id');
        })
        ->leftJoinSub($resultSub, 'lr', function ($join) {
            $join->on('lr.bubble_game_id', '=', 'bg.id');
        })
        ->leftJoin('bubble_game_results as bgr', 'bgr.id', '=', 'lr.latest_id')
        ->leftJoinSub($attemptStatSub, 'ats', function ($join) {
            $join->on('ats.bubble_game_id', '=', 'bg.id');
        })
        ->leftJoinSub($assignSub, 'asgn', function ($join) {
            $join->on('asgn.bubble_game_id', '=', 'bg.id');
        })
        ->whereNull('bg.deleted_at')
        ->where('bg.status', '=', 'active');

    if (!in_array($role, ['admin', 'super_admin'], true)) {
        $q->whereNotNull('asgn.assigned_at');
    }

    if ($search !== '') {
        $q->where(function ($w) use ($search, $hasInstr, $hasInstrAlt) {
            $w->where('bg.title', 'like', "%{$search}%")
              ->orWhere('bg.description', 'like', "%{$search}%");

            // ✅ include instructions in search if available
            if ($hasInstr)    $w->orWhere('bg.instructions', 'like', "%{$search}%");
            if ($hasInstrAlt) $w->orWhere('bg.instruction', 'like', "%{$search}%");
        });
    }

    $select = [
        'bg.id',
        'bg.uuid',
        'bg.title',

        // ✅ description always
        'bg.description as description',

        // ✅ instructions (safe fallback)
        $hasInstr
            ? 'bg.instructions as instructions'
            : ($hasInstrAlt ? 'bg.instruction as instructions' : DB::raw("'' as instructions")),

        // ✅ instructions_html (safe fallback)
        $hasInstrHtml
            ? 'bg.instructions_html as instructions_html'
            : ($hasInstrHtmlAlt ? 'bg.instruction_html as instructions_html' : DB::raw("'' as instructions_html")),

        'bg.max_attempts',
        'bg.per_question_time_sec',
        'bg.allow_skip',
        'bg.status',
        'bg.created_at',

        DB::raw('COALESCE(qc.total_questions, 0) as total_questions'),
        DB::raw('COALESCE(ats.attempts_count, 0) as attempts_count'),
        DB::raw('COALESCE(ats.max_attempt_no, 0) as max_attempt_no'),
        'asgn.assigned_at as assigned_at',

        'bgr.id         as result_id',
        'bgr.created_at as result_created_at',
        'bgr.score      as result_score',
        'bgr.attempt_no as result_attempt_no',
    ];

    $paginator = $q->select($select)
        ->orderBy('bg.created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    $items = collect($paginator->items())->map(function ($row) {
        $totalQuestions = (int) ($row->total_questions ?? 0);
        $perQSec        = (int) ($row->per_question_time_sec ?? 0);

        $totalMinutes = null;
        if ($totalQuestions > 0 && $perQSec > 0) {
            $totalMinutes = (int) ceil(($totalQuestions * $perQSec) / 60);
        }

        $allowed = ($row->max_attempts !== null) ? (int) $row->max_attempts : 1;

        $usedByCount = (int) ($row->attempts_count ?? 0);
        $usedByMaxNo = (int) ($row->max_attempt_no ?? 0);
        $used = max($usedByCount, $usedByMaxNo);

        $remaining = $allowed > 0 ? max($allowed - $used, 0) : 0;

        $maxReached = ($allowed > 0) ? ($used >= $allowed) : false;
        $canAttempt = !$maxReached;

        $myStatus = $row->result_id ? 'completed' : 'upcoming';

        return [
            'id'              => (int) $row->id,
            'uuid'            => (string) $row->uuid,
            'assigned_at'     => $row->assigned_at
                ? \Carbon\Carbon::parse($row->assigned_at)->toDateTimeString()
                : null,

            'title'           => (string) ($row->title ?? 'Bubble Game'),

            // ✅ NEW: send both separately
            'description'     => (string) ($row->description ?? ''),
            'instructions_html' => (string) ($row->instructions_html ?? ''),

            // ✅ keep old behavior (so UI won’t break)
            'excerpt'         => (string) ($row->description ?? ''),

            'total_time'      => $totalMinutes,
            'total_questions' => $totalQuestions,

            'total_attempts'  => $allowed,

            'max_attempts_allowed' => $allowed,
            'my_attempts'           => $used,
            'remaining_attempts'    => $remaining,
            'max_attempt_reached'   => $maxReached,
            'can_attempt'           => $canAttempt,

            'is_public'       => false,
            'status'          => (string) ($row->status ?? 'active'),

            'created_at'      => $row->created_at
                ? \Carbon\Carbon::parse($row->created_at)->toDateTimeString()
                : null,

            'my_status'       => $myStatus,

            'result' => $row->result_id ? [
                'id'         => (int) $row->result_id,
                'created_at' => $row->result_created_at
                    ? \Carbon\Carbon::parse($row->result_created_at)->toDateTimeString()
                    : null,
                'score'      => $row->result_score !== null ? (int) $row->result_score : null,
                'attempt_no' => $row->result_attempt_no !== null ? (int) $row->result_attempt_no : null,
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
