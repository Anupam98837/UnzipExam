<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BubbleGameController extends Controller
{
    // ✅ Add inside BubbleGameController
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

    // ✅ Filter by status (active/inactive/archived)
    // (skip empty string)
    if ($request->has('status') && $request->status !== '') {
        $query->where('bubble_game.status', $request->status);
    }

    // ✅ Search by title
    if ($request->has('search') && trim($request->search) !== '') {
        $query->where('bubble_game.title', 'like', '%' . trim($request->search) . '%');
    }

    // ✅ Safe Order by (WHITELIST)
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

    // ✅ Paginate
    $perPage = max(1, (int) $request->get('per_page', 15));
    $page    = max(1, (int) $request->get('page', 1));
    $offset  = ($page - 1) * $perPage;

    $total = (clone $query)->count();
    $bubbleGames = $query->offset($offset)->limit($perPage)->get();

    // ✅ Decode JSON fields
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

        // Prepare insert data
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

        $gameId = DB::table('bubble_game')->insertGetId($insertData);

        // Get created game with creator info
        $bubbleGame = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.id', $gameId)
            ->first();

        $bubbleGame->metadata = json_decode($bubbleGame->metadata);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game created successfully',
            'data' => $bubbleGame
        ], 201);
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

        $bubbleGame->metadata = json_decode($bubbleGame->metadata);

        return response()->json([
            'success' => true,
            'data' => $bubbleGame
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
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
        $updateData = [
            'updated_at_ip' => $request->ip(),
            'updated_at' => now()
        ];

        // Add each field to update data if present
        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }
        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['max_attempts'])) {
            $updateData['max_attempts'] = $data['max_attempts'];
        }
        if (isset($data['per_question_time_sec'])) {
            $updateData['per_question_time_sec'] = $data['per_question_time_sec'];
        }
        if (isset($data['is_question_random'])) {
            $updateData['is_question_random'] = $data['is_question_random'];
        }
        if (isset($data['is_bubble_positions_random'])) {
            $updateData['is_bubble_positions_random'] = $data['is_bubble_positions_random'];
        }
        if (isset($data['allow_skip'])) {
            $updateData['allow_skip'] = $data['allow_skip'];
        }
        if (isset($data['points_correct'])) {
            $updateData['points_correct'] = $data['points_correct'];
        }
        if (isset($data['points_wrong'])) {
            $updateData['points_wrong'] = $data['points_wrong'];
        }
        if (isset($data['show_solution_after'])) {
            $updateData['show_solution_after'] = $data['show_solution_after'];
        }
        if (isset($data['instructions_html'])) {
            $updateData['instructions_html'] = $data['instructions_html'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['metadata'])) {
            $updateData['metadata'] = json_encode($data['metadata']);
        }

        DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->update($updateData);

        // Get updated game with creator info
        $updatedGame = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.uuid', $uuid)
            ->first();

        $updatedGame->metadata = json_decode($updatedGame->metadata);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game updated successfully',
            'data' => $updatedGame
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $uuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->update(['deleted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game deleted successfully'
        ]);
    }

    /**
     * Restore a soft deleted resource.
     */
    public function restore(string $uuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNotNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found or not deleted'
            ], 404);
        }

        DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->update(['deleted_at' => null]);

        // Get restored game with creator info
        $restoredGame = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.uuid', $uuid)
            ->first();

        $restoredGame->metadata = json_decode($restoredGame->metadata);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game restored successfully',
            'data' => $restoredGame
        ]);
    }

    /**
     * Permanently delete a resource.
     */
    public function forceDelete(string $uuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bubble game permanently deleted'
        ]);
    }

    /**
     * Duplicate an existing bubble game.
     */
    public function duplicate(string $uuid)
    {
        $original = DB::table('bubble_game')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$original) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        // Prepare duplicate data
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
            'created_at_ip' => request()->ip(),
            'updated_at_ip' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        $duplicateId = DB::table('bubble_game')->insertGetId($duplicateData);

        // Get duplicated game with creator info
        $duplicate = DB::table('bubble_game')
            ->leftJoin('users', 'bubble_game.created_by', '=', 'users.id')
            ->select(
                'bubble_game.*',
                'users.name as creator_name',
                'users.email as creator_email'
            )
            ->where('bubble_game.id', $duplicateId)
            ->first();

        $duplicate->metadata = json_decode($duplicate->metadata);

        return response()->json([
            'success' => true,
            'message' => 'Bubble game duplicated successfully',
            'data' => $duplicate
        ], 201);
    }
   /* =========================
 * MY BUBBLE GAMES (student)
 * GET /api/bubble-games/my
 * - Shows games assigned to logged-in student (user_bubble_game_assignments)
 * - Admin/Super Admin: can see all active games
 * - Includes latest result for that student (and computed status)
 * - Computes total_questions + total_time from bubble_game_questions + per_question_time_sec
 * ========================= */
public function myBubbleGames(Request $r)
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

    // ---- Subquery: question count per game (active questions only) ----
    $qCountSub = DB::table('bubble_game_questions')
        ->select('bubble_game_id', DB::raw('COUNT(*) as total_questions'))
        ->where('status', '=', 'active')
        ->groupBy('bubble_game_id');

    // ---- Subquery: latest result per game for this user ----
    $resultSub = DB::table('bubble_game_results')
        ->select('bubble_game_id', DB::raw('MAX(id) as latest_id'))
        ->where('user_id', $userId)
        ->whereNull('deleted_at')
        ->groupBy('bubble_game_id');

    // ✅ NEW: attempts stats per game for this user
    // We keep BOTH COUNT(*) and MAX(attempt_no) to be super safe.
    $attemptStatSub = DB::table('bubble_game_results')
        ->select([
            'bubble_game_id',
            DB::raw('COUNT(*) as attempts_count'),
            DB::raw('COALESCE(MAX(attempt_no), 0) as max_attempt_no'),
        ])
        ->where('user_id', $userId)
        ->whereNull('deleted_at')
        ->groupBy('bubble_game_id');

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
        ->whereNull('bg.deleted_at')
        ->where('bg.status', '=', 'active');

    // ✅ STUDENT VISIBILITY: only assigned games (active assignment, not deleted)
    if (!in_array($role, ['admin','super_admin'], true)) {
        $q->whereExists(function ($sq) use ($userId) {
            $sq->select(DB::raw(1))
                ->from('user_bubble_game_assignments as uga')
                ->whereColumn('uga.bubble_game_id', 'bg.id')
                ->where('uga.user_id', '=', $userId)
                ->where('uga.status', '=', 'active')
                ->whereNull('uga.deleted_at');
        });
    }

    // Optional text search
    if ($search !== '') {
        $q->where(function ($w) use ($search) {
            $w->where('bg.title', 'like', "%{$search}%")
              ->orWhere('bg.description', 'like', "%{$search}%");
        });
    }

    $select = [
        'bg.id',
        'bg.uuid',
        'bg.title',
        'bg.description',
        'bg.max_attempts',
        'bg.per_question_time_sec',
        'bg.allow_skip',
        'bg.status',
        'bg.created_at',

        DB::raw('COALESCE(qc.total_questions, 0) as total_questions'),

        // ✅ NEW attempt stats
        DB::raw('COALESCE(ats.attempts_count, 0) as attempts_count'),
        DB::raw('COALESCE(ats.max_attempt_no, 0) as max_attempt_no'),

        // latest result
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

        // total_time shown as minutes (UI prints "min")
        $totalMinutes = null;
        if ($totalQuestions > 0 && $perQSec > 0) {
            $totalMinutes = (int) ceil(($totalQuestions * $perQSec) / 60);
        }

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
        // We keep it simple: if any result exists => completed else upcoming
        // (Continue/in_progress depends on your gameplay logic; if you later add it, UI supports it.)
        $myStatus = $row->result_id ? 'completed' : 'upcoming';

        return [
            'id'              => (int) $row->id,
            'uuid'            => (string) $row->uuid,

            // UI fields
            'title'           => (string) ($row->title ?? 'Bubble Game'),
            'excerpt'         => (string) ($row->description ?? ''),
            'total_time'      => $totalMinutes,              // minutes
            'total_questions' => $totalQuestions,

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