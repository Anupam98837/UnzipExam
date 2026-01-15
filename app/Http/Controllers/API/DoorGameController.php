<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DoorGameController extends Controller
{
    /**
     * Display a listing of door games
     */
    public function index(Request $request)
    {
        Log::info('DoorGame.index: start', [
            'ip' => $request->ip(),
            'query' => $request->query(),
        ]);

        try {
            $query = DB::table('door_game')->whereNull('deleted_at');

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
                Log::info('DoorGame.index: status filter applied', ['status' => $request->status]);
            }

            // Search by title if provided
            if ($request->has('search')) {
                $query->where('title', 'like', '%' . $request->search . '%');
                Log::info('DoorGame.index: search filter applied', ['search' => $request->search]);
            }

            $perPage = (int) $request->input('per_page', 15);
            $page = (int) $request->input('page', 1);
            $offset = ($page - 1) * $perPage;

            Log::info('DoorGame.index: pagination', [
                'per_page' => $perPage,
                'page' => $page,
                'offset' => $offset,
            ]);

            $total = $query->count();

            $games = $query->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            Log::info('DoorGame.index: success', [
                'total' => $total,
                'returned' => $games->count(),
                'last_page' => (int) ceil($total / max($perPage, 1)),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $games,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / max($perPage, 1)),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGame.index: exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching door games',
            ], 500);
        }
    }

    /**
     * Store a newly created door game
     */
    public function store(Request $request)
    {
        Log::info('DoorGame.store: start', [
            'ip' => $request->ip(),
            'payload_keys' => array_keys($request->all()),
        ]);

        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin', 'creator']);
        if ($authCheck) {
            Log::warning('DoorGame.store: unauthorized', [
                'actor' => $this->actor($request),
                'allowed' => ['admin', 'creator'],
            ]);
            return $authCheck;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:180',
            'description' => 'nullable|string',
            'instructions_html' => 'nullable|string',
            'show_solution_after' => 'in:never,after_each,after_finish',
            'grid_dim' => 'required|integer|min:1|max:10',
            'grid_json' => 'required|json',
            'max_attempts' => 'integer|min:1',
            'time_limit_sec' => 'integer|min:1',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            Log::warning('DoorGame.store: validation failed', [
                'actor' => $this->actor($request),
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate grid_json structure
        $gridData = json_decode($request->grid_json, true);
        $expectedCells = (int) $request->grid_dim * (int) $request->grid_dim;

        Log::info('DoorGame.store: grid meta', [
            'grid_dim' => (int) $request->grid_dim,
            'expected_cells' => $expectedCells,
            'grid_json_len' => is_string($request->grid_json) ? strlen($request->grid_json) : null,
            'decoded_is_array' => is_array($gridData),
            'decoded_count' => is_array($gridData) ? count($gridData) : null,
        ]);

        if (!is_array($gridData) || count($gridData) !== $expectedCells) {
            Log::warning('DoorGame.store: invalid grid_json structure', [
                'grid_dim' => (int) $request->grid_dim,
                'expected_cells' => $expectedCells,
                'decoded_count' => is_array($gridData) ? count($gridData) : null,
            ]);

            return response()->json([
                'success' => false,
                'errors' => [
                    'grid_json' => ["Grid must contain exactly {$expectedCells} cells for a {$request->grid_dim}x{$request->grid_dim} grid"]
                ],
            ], 422);
        }

        $actor = $this->actor($request);

        try {
            DB::beginTransaction();

            $uuid = (string) Str::uuid();

            $id = DB::table('door_game')->insertGetId([
                'uuid' => $uuid,
                'title' => $request->title,
                'description' => $request->description,
                'instructions_html' => $request->instructions_html,
                'show_solution_after' => $request->input('show_solution_after', 'after_finish'),
                'grid_dim' => $request->grid_dim,
                'grid_json' => $request->grid_json,
                'max_attempts' => $request->input('max_attempts', 1),
                'time_limit_sec' => $request->input('time_limit_sec', 30),
                'status' => $request->input('status', 'active'),
                'created_by' => $actor['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $game = DB::table('door_game')->find($id);

            DB::commit();

            Log::info('DoorGame.store: created', [
                'actor' => $actor,
                'door_game_id' => $id,
                'uuid' => $uuid,
                'status' => $game->status ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Door game created successfully',
                'data' => $game,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('DoorGame.store: exception', [
                'actor' => $actor,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while creating door game',
            ], 500);
        }
    }

    /**
     * Display the specified door game
     */
    public function show($id)
    {
        Log::info('DoorGame.show: start', ['id_or_uuid' => $id]);

        try {
            $game = DB::table('door_game')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('uuid', $id);
                })
                ->first();

            if (!$game) {
                Log::warning('DoorGame.show: not found', ['id_or_uuid' => $id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Door game not found',
                ], 404);
            }

            Log::info('DoorGame.show: success', [
                'door_game_id' => $game->id ?? null,
                'uuid' => $game->uuid ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $game,
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGame.show: exception', [
                'id_or_uuid' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching door game',
            ], 500);
        }
    }

    /**
     * Update the specified door game
     */
    public function update(Request $request, $id)
    {
        Log::info('DoorGame.update: start', [
            'id_or_uuid' => $id,
            'ip' => $request->ip(),
            'payload_keys' => array_keys($request->all()),
        ]);

        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin', 'creator']);
        if ($authCheck) {
            Log::warning('DoorGame.update: unauthorized', [
                'actor' => $this->actor($request),
                'allowed' => ['admin', 'creator'],
                'id_or_uuid' => $id,
            ]);
            return $authCheck;
        }

        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$game) {
            Log::warning('DoorGame.update: not found', ['id_or_uuid' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Door game not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|max:180',
            'description' => 'nullable|string',
            'instructions_html' => 'nullable|string',
            'show_solution_after' => 'in:never,after_each,after_finish',
            'grid_dim' => 'integer|min:1|max:10',
            'grid_json' => 'json',
            'max_attempts' => 'integer|min:1',
            'time_limit_sec' => 'integer|min:1',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            Log::warning('DoorGame.update: validation failed', [
                'actor' => $this->actor($request),
                'id_or_uuid' => $id,
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate grid_json if provided (grid_dim optional, fallback to existing)
        if ($request->has('grid_json')) {
            $gridDim = (int) $request->input('grid_dim', $game->grid_dim);
            $gridData = json_decode($request->grid_json, true);
            $expectedCells = $gridDim * $gridDim;

            Log::info('DoorGame.update: grid meta', [
                'id_or_uuid' => $id,
                'grid_dim' => $gridDim,
                'expected_cells' => $expectedCells,
                'grid_json_len' => is_string($request->grid_json) ? strlen($request->grid_json) : null,
                'decoded_is_array' => is_array($gridData),
                'decoded_count' => is_array($gridData) ? count($gridData) : null,
            ]);

            if (!is_array($gridData) || count($gridData) !== $expectedCells) {
                Log::warning('DoorGame.update: invalid grid_json structure', [
                    'id_or_uuid' => $id,
                    'grid_dim' => $gridDim,
                    'expected_cells' => $expectedCells,
                    'decoded_count' => is_array($gridData) ? count($gridData) : null,
                ]);

                return response()->json([
                    'success' => false,
                    'errors' => [
                        'grid_json' => ["Grid must contain exactly {$expectedCells} cells"]
                    ],
                ], 422);
            }
        }

        $updateData = array_filter([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'instructions_html' => $request->input('instructions_html'),
            'show_solution_after' => $request->input('show_solution_after'),
            'grid_dim' => $request->input('grid_dim'),
            'grid_json' => $request->input('grid_json'),
            'max_attempts' => $request->input('max_attempts'),
            'time_limit_sec' => $request->input('time_limit_sec'),
            'status' => $request->input('status'),
        ], function ($value) {
            return $value !== null;
        });

        if (empty($updateData)) {
            Log::info('DoorGame.update: no changes provided', [
                'id_or_uuid' => $id,
                'door_game_id' => $game->id,
                'actor' => $this->actor($request),
            ]);

            $updatedGame = DB::table('door_game')->find($game->id);

            return response()->json([
                'success' => true,
                'message' => 'Door game updated successfully',
                'data' => $updatedGame,
            ]);
        }

        try {
            DB::beginTransaction();

            $updateData['updated_at'] = now();

            Log::info('DoorGame.update: updating', [
                'door_game_id' => $game->id,
                'uuid' => $game->uuid,
                'fields' => array_keys($updateData),
                'actor' => $this->actor($request),
            ]);

            DB::table('door_game')
                ->where('id', $game->id)
                ->update($updateData);

            $updatedGame = DB::table('door_game')->find($game->id);

            DB::commit();

            Log::info('DoorGame.update: success', [
                'door_game_id' => $game->id,
                'uuid' => $game->uuid,
                'actor' => $this->actor($request),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Door game updated successfully',
                'data' => $updatedGame,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('DoorGame.update: exception', [
                'door_game_id' => $game->id ?? null,
                'uuid' => $game->uuid ?? null,
                'id_or_uuid' => $id,
                'actor' => $this->actor($request),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while updating door game',
            ], 500);
        }
    }

    /**
     * Remove the specified door game (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        Log::info('DoorGame.destroy: start', [
            'id_or_uuid' => $id,
            'ip' => $request->ip(),
        ]);

        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin']);
        if ($authCheck) {
            Log::warning('DoorGame.destroy: unauthorized', [
                'actor' => $this->actor($request),
                'allowed' => ['admin'],
                'id_or_uuid' => $id,
            ]);
            return $authCheck;
        }

        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$game) {
            Log::warning('DoorGame.destroy: not found', ['id_or_uuid' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Door game not found',
            ], 404);
        }

        try {
            DB::table('door_game')
                ->where('id', $game->id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            Log::info('DoorGame.destroy: soft deleted', [
                'door_game_id' => $game->id,
                'uuid' => $game->uuid,
                'actor' => $this->actor($request),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Door game deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGame.destroy: exception', [
                'door_game_id' => $game->id ?? null,
                'uuid' => $game->uuid ?? null,
                'id_or_uuid' => $id,
                'actor' => $this->actor($request),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while deleting door game',
            ], 500);
        }
    }

    /**
     * Get active games list (public endpoint)
     */
    public function activeGames(Request $request)
    {
        Log::info('DoorGame.activeGames: start', [
            'ip' => $request->ip(),
            'query' => $request->query(),
        ]);

        try {
            $perPage = (int) $request->input('per_page', 15);
            $page = (int) $request->input('page', 1);
            $offset = ($page - 1) * $perPage;

            $query = DB::table('door_game')
                ->whereNull('deleted_at')
                ->where('status', 'active');

            $total = $query->count();

            $games = $query->select(['id', 'uuid', 'title', 'description', 'grid_dim', 'max_attempts', 'time_limit_sec', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            Log::info('DoorGame.activeGames: success', [
                'total' => $total,
                'returned' => $games->count(),
                'current_page' => $page,
                'per_page' => $perPage,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $games,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / max($perPage, 1)),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGame.activeGames: exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching active games',
            ], 500);
        }
    }

    /**
     * Get game details for playing (public endpoint)
     * Returns game without solution data
     */
    public function playGame($id)
    {
        Log::info('DoorGame.playGame: start', ['id_or_uuid' => $id]);

        try {
            $game = DB::table('door_game')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('uuid', $id);
                })
                ->first();

            if (!$game) {
                Log::warning('DoorGame.playGame: not found or not active', ['id_or_uuid' => $id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Game not found or not active',
                ], 404);
            }

            $gridData = json_decode($game->grid_json, true);

            Log::info('DoorGame.playGame: grid meta', [
                'door_game_id' => $game->id,
                'uuid' => $game->uuid,
                'grid_dim' => $game->grid_dim,
                'decoded_is_array' => is_array($gridData),
                'decoded_count' => is_array($gridData) ? count($gridData) : null,
            ]);

            if (!is_array($gridData)) {
                Log::warning('DoorGame.playGame: invalid stored grid_json', [
                    'door_game_id' => $game->id,
                    'uuid' => $game->uuid,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Game grid is invalid',
                ], 422);
            }

            // Remove solution keys from each cell for initial play
            $playGrid = array_map(function ($cell) {
                return [
                    'id' => $cell['id'] ?? null,
                    'label' => $cell['label'] ?? null,
                    'type' => $cell['type'] ?? 'door',
                    // Don't expose 'is_correct' or similar fields
                ];
            }, $gridData);

            Log::info('DoorGame.playGame: success', [
                'door_game_id' => $game->id,
                'uuid' => $game->uuid,
                'returned_cells' => count($playGrid),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $game->id,
                    'uuid' => $game->uuid,
                    'title' => $game->title,
                    'description' => $game->description,
                    'instructions_html' => $game->instructions_html,
                    'grid_dim' => $game->grid_dim,
                    'grid' => $playGrid,
                    'max_attempts' => $game->max_attempts,
                    'time_limit_sec' => $game->time_limit_sec,
                    'show_solution_after' => $game->show_solution_after,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGame.playGame: exception', [
                'id_or_uuid' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching game for play',
            ], 500);
        }
    }

    // Helper methods from your code
    private function actor(Request $request): array
    {
        $actor = [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];

        Log::debug('DoorGame.actor', $actor);

        return $actor;
    }

    private function requireRole(Request $request, array $allowed)
    {
        $actor = $this->actor($request);

        if (!$actor['role'] || !in_array($actor['role'], $allowed, true)) {
            Log::warning('DoorGame.requireRole: forbidden', [
                'actor' => $actor,
                'allowed' => $allowed,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error'   => 'Unauthorized Access',
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        Log::info('DoorGame.requireRole: allowed', [
            'actor' => $actor,
            'allowed' => $allowed,
        ]);

        return null;
    }
    public function myDoorGames(Request $r)
{
    // ✅ Allow student/admin/super_admin
    if ($resp = $this->requireRole($r, ['student','admin','super_admin'])) return $resp;

    $actor  = $this->actor($r);
    $userId = (int) ($actor['id'] ?? 0);
    $role   = (string) ($actor['role'] ?? '');

    if (!$userId) {
        return response()->json(['success' => false, 'message' => 'Unable to resolve user from token'], 403);
    }

    $page    = max(1, (int) $r->query('page', 1));
    $perPage = max(1, min(50, (int) $r->query('per_page', 12)));
    $search  = trim((string) $r->query('q', ''));

    // ---- Subquery: latest result per game for this user ----
    $resultSub = DB::table('door_game_results')
        ->select('door_game_id', DB::raw('MAX(id) as latest_id'))
        ->where('user_id', $userId)
        ->whereNull('deleted_at')
        ->groupBy('door_game_id');

    // ✅ attempts stats per game for this user (COUNT + MAX(attempt_no))
    $attemptStatSub = DB::table('door_game_results')
        ->select([
            'door_game_id',
            DB::raw('COUNT(*) as attempts_count'),
            DB::raw('COALESCE(MAX(attempt_no), 0) as max_attempt_no'),
        ])
        ->where('user_id', $userId)
        ->whereNull('deleted_at')
        ->groupBy('door_game_id');

    $q = DB::table('door_game as dg')
        ->leftJoinSub($resultSub, 'lr', function ($join) {
            $join->on('lr.door_game_id', '=', 'dg.id');
        })
        ->leftJoin('door_game_results as dgr', 'dgr.id', '=', 'lr.latest_id')
        ->leftJoinSub($attemptStatSub, 'ats', function ($join) {
            $join->on('ats.door_game_id', '=', 'dg.id');
        })
        ->whereNull('dg.deleted_at')
        ->where('dg.status', '=', 'active');

    // ✅ STUDENT VISIBILITY: only assigned games
    // 🔁 change table name if yours differs
    if (!in_array($role, ['admin','super_admin'], true)) {
        $q->whereExists(function ($sq) use ($userId) {
            $sq->select(DB::raw(1))
                ->from('user_door_game_assignments as uga')
                ->whereColumn('uga.door_game_id', 'dg.id')
                ->where('uga.user_id', '=', $userId)
                ->where('uga.status', '=', 'active')
                ->whereNull('uga.deleted_at');
        });
    }

    // Optional text search
    if ($search !== '') {
        $q->where(function ($w) use ($search) {
            $w->where('dg.title', 'like', "%{$search}%")
              ->orWhere('dg.description', 'like', "%{$search}%");
        });
    }

    $select = [
        'dg.id',
        'dg.uuid',
        'dg.title',
        'dg.description',
        'dg.grid_dim',
        'dg.max_attempts',
        'dg.time_limit_sec',
        'dg.status',
        'dg.created_at',

        // attempt stats
        DB::raw('COALESCE(ats.attempts_count, 0) as attempts_count'),
        DB::raw('COALESCE(ats.max_attempt_no, 0) as max_attempt_no'),

        // latest result
        'dgr.id         as result_id',
        'dgr.created_at as result_created_at',
        'dgr.score      as result_score',
        'dgr.attempt_no as result_attempt_no',
        'dgr.status     as result_status',
        'dgr.time_taken_ms as result_time_taken_ms',
    ];

    $paginator = $q->select($select)
        ->orderBy('dg.created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    $items = collect($paginator->items())->map(function ($row) {

        // ✅ allowed attempts (default 1 if null)
        $allowed = ($row->max_attempts !== null)
            ? (int) $row->max_attempts
            : 1;

        // ✅ used attempts (safe: max(attempt_no, count))
        $usedByCount = (int) ($row->attempts_count ?? 0);
        $usedByMaxNo = (int) ($row->max_attempt_no ?? 0);
        $used = max($usedByCount, $usedByMaxNo);

        // ✅ remaining attempts
        $remaining = $allowed > 0 ? max($allowed - $used, 0) : 0;

        $maxReached = ($allowed > 0) ? ($used >= $allowed) : false;
        $canAttempt = !$maxReached;

        // ✅ my_status:
        // - if latest result exists and its status is in_progress => in_progress
        // - else if any result exists => completed
        // - else upcoming
        $latestStatus = (string) ($row->result_status ?? '');
        if ($row->result_id && $latestStatus === 'in_progress') {
            $myStatus = 'in_progress';
        } elseif ($row->result_id) {
            $myStatus = 'completed';
        } else {
            $myStatus = 'upcoming';
        }

        // total_time for UI (minutes) — your door game uses time_limit_sec
        $totalMinutes = null;
        $limitSec = (int) ($row->time_limit_sec ?? 0);
        if ($limitSec > 0) $totalMinutes = (int) ceil($limitSec / 60);

        // total_questions is not meaningful for door game; keep 0 for UI compatibility
        $totalQuestions = 0;

        return [
            'id'              => (int) $row->id,
            'uuid'            => (string) $row->uuid,

            // UI fields
            'title'           => (string) ($row->title ?? 'Door Game'),
            'excerpt'         => (string) ($row->description ?? ''),
            'total_time'      => $totalMinutes, // minutes for UI display
            'total_questions' => $totalQuestions,

            // extra info useful for door game UI
            'grid_dim'        => (int) ($row->grid_dim ?? 3),
            'time_limit_sec'  => (int) ($row->time_limit_sec ?? 0),

            // ✅ old key (keep for backward compatibility)
            'total_attempts'  => $allowed,

            // ✅ NEW KEYS (frontend max-attempt logic uses these)
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

            // Result info (if exists)
            'result' => $row->result_id ? [
                'id'           => (int) $row->result_id,
                'created_at'   => $row->result_created_at
                    ? \Carbon\Carbon::parse($row->result_created_at)->toDateTimeString()
                    : null,
                'score'        => $row->result_score !== null ? (int) $row->result_score : null,
                'attempt_no'   => $row->result_attempt_no !== null ? (int) $row->result_attempt_no : null,
                'status'       => (string) ($row->result_status ?? null),
                'time_taken_ms'=> $row->result_time_taken_ms !== null ? (int) $row->result_time_taken_ms : null,
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
