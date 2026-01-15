<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Illuminate\Validation\Rule;
use Carbon\Carbon;


class BubbleGameResultController extends Controller
{
     private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }
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
    private function gameByKey(string $key)
{
    return DB::table('bubble_game')
        ->whereNull('deleted_at')
        ->where(function ($w) use ($key) {
            if (is_numeric($key)) $w->where('id', (int) $key);
            else $w->where('uuid', (string) $key);
        })
        ->first();
}

private function normalizeRole(?string $role): string
{
    return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)$role));
}
    public function index(Request $request)
{
    /*
     |--------------------------------------------------------------------------
     | Subquery: question stats per game (no row explosion)
     |--------------------------------------------------------------------------
     */
    $qStats = DB::table('bubble_game_questions as bq')
        ->selectRaw('bq.bubble_game_id, COUNT(*) as total_questions, COALESCE(SUM(bq.points),0) as total_points')
        ->where('bq.status', 'active')
        ->groupBy('bq.bubble_game_id');

    /*
     |--------------------------------------------------------------------------
     | Base query (results + game + user + assignment + question stats)
     |--------------------------------------------------------------------------
     | assignment table: user_bubble_game_assignments
     |--------------------------------------------------------------------------
     */
    $query = DB::table('bubble_game_results as r')
        ->join('bubble_game as g', 'r.bubble_game_id', '=', 'g.id')
        ->join('users as u', 'r.user_id', '=', 'u.id')
        ->leftJoin('user_bubble_game_assignments as a', function ($j) {
            $j->on('a.user_id', '=', 'r.user_id')
              ->on('a.bubble_game_id', '=', 'r.bubble_game_id')
              ->whereNull('a.deleted_at');
        })
        ->leftJoinSub($qStats, 'qs', function ($j) {
            $j->on('qs.bubble_game_id', '=', 'g.id');
        })
        ->whereNull('r.deleted_at')
        ->select([
            // result
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.bubble_game_id',
            'r.user_id',
            'r.attempt_no',
            'r.score',
            'r.user_answer_json',
            'r.created_at as result_created_at',
            'r.updated_at as result_updated_at',

            // game
            'g.id as game_id',
            'g.uuid as game_uuid',
            'g.title as game_title',
            'g.max_attempts',
            'g.per_question_time_sec',
            'g.status as game_status',

            // user
            'u.id as student_id',
            'u.uuid as student_uuid',
            'u.name as student_name',
            'u.email as student_email',

            // assignment
            'a.id as assignment_id',
            'a.uuid as assignment_uuid',
            'a.assignment_code',
            'a.status as assignment_status',
            'a.assigned_at',

            // question stats
            DB::raw('COALESCE(qs.total_questions,0) as total_questions'),
            DB::raw('COALESCE(qs.total_points,0) as total_points'),
        ]);

    /*
     |--------------------------------------------------------------------------
     | Filters
     |--------------------------------------------------------------------------
     */
    if ($request->filled('bubble_game_id')) {
        $query->where('r.bubble_game_id', (int) $request->get('bubble_game_id'));
    }

    if ($request->filled('game_uuid')) {
        $query->where('g.uuid', $request->string('game_uuid')->toString());
    }

    if ($request->filled('user_id')) {
        $query->where('r.user_id', (int) $request->get('user_id'));
    }

    if ($request->filled('student_email')) {
        $query->where('u.email', 'like', '%' . $request->string('student_email')->toString() . '%');
    }

    if ($request->boolean('my_results')) {
        $query->where('r.user_id', (int) Auth::id());
    }

    if ($request->filled('attempt_no')) {
        $query->where('r.attempt_no', (int) $request->get('attempt_no'));
    }

    // Date range (YYYY-MM-DD)
    if ($request->filled('from')) {
        $query->whereDate('r.created_at', '>=', $request->string('from')->toString());
    }
    if ($request->filled('to')) {
        $query->whereDate('r.created_at', '<=', $request->string('to')->toString());
    }

    // Search (supports both "q" and "search")
    $search = trim((string) ($request->get('q') ?? $request->get('search') ?? ''));
    if ($search !== '') {
        $query->where(function ($w) use ($search) {
            $w->where('u.name', 'like', "%{$search}%")
              ->orWhere('u.email', 'like', "%{$search}%")
              ->orWhere('g.title', 'like', "%{$search}%")
              ->orWhere('r.uuid', 'like', "%{$search}%")
              ->orWhere('a.assignment_code', 'like', "%{$search}%");
        });
    }

    /*
     |--------------------------------------------------------------------------
     | Sorting (matches your frontend sort param)
     |--------------------------------------------------------------------------
     */
    $sort = (string) $request->get('sort', '-result_created_at'); // default newest first
    $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
    $key  = ltrim($sort, '-');

    $sortMap = [
        'student_name'      => 'u.name',
        'game_title'        => 'g.title',
        'score'             => 'r.score',
        'accuracy'          => 'r.score',      // since percentage doesn't exist, use score ordering
        'result_created_at' => 'r.created_at',
        'attempt_no'        => 'r.attempt_no',
    ];

    $orderCol = $sortMap[$key] ?? 'r.created_at';
    $query->orderBy($orderCol, $dir);

    /*
     |--------------------------------------------------------------------------
     | No pagination support
     |--------------------------------------------------------------------------
     */
    if ($request->boolean('paginate') === false || $request->get('paginate') === 'false') {
        $rows = $query->get();
        return response()->json([
            'success' => true,
            'data' => $this->transformBubbleRows($rows),
            'pagination' => null,
        ]);
    }

    /*
     |--------------------------------------------------------------------------
     | Pagination (manual)
     |--------------------------------------------------------------------------
     */
    $perPage = max(1, min((int) $request->get('per_page', 20), 100));
    $page    = max(1, (int) $request->get('page', 1));
    $offset  = ($page - 1) * $perPage;

    $total = (clone $query)->count(); // safe (no groupBy)
    $rows  = $query->offset($offset)->limit($perPage)->get();

    return response()->json([
        'success' => true,
        'data' => $this->transformBubbleRows($rows),
        'pagination' => [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ],
    ]);
}

/**
 * Convert flat DB rows -> nested objects for your Blade/JS.
 */
private function transformBubbleRows($rows)
{
    return collect($rows)->map(function ($r) {
        $answers = null;
        if (isset($r->user_answer_json) && is_string($r->user_answer_json) && $r->user_answer_json !== '') {
            $answers = json_decode($r->user_answer_json, true);
        } elseif (is_array($r->user_answer_json)) {
            $answers = $r->user_answer_json;
        }

        $totalQuestions = (int) ($r->total_questions ?? 0);
        $score          = (int) ($r->score ?? 0);

        // If total questions exist, you can show an "accuracy%" as score/total_points OR score/total_questions.
        // Here: score vs total_points if points are used; else fallback to total_questions.
        $den = (int) ($r->total_points ?? 0);
        if ($den <= 0) $den = $totalQuestions;
        $accuracy = ($den > 0) ? round(($score / $den) * 100, 2) : null;

        return [
            'student' => [
                'id'    => (int) ($r->student_id ?? 0),
                'uuid'  => (string) ($r->student_uuid ?? ''),
                'name'  => (string) ($r->student_name ?? ''),
                'email' => (string) ($r->student_email ?? ''),
            ],
            'game' => [
                'id'              => (int) ($r->game_id ?? 0),
                'uuid'            => (string) ($r->game_uuid ?? ''),
                'title'           => (string) ($r->game_title ?? ''),
                'status'          => (string) ($r->game_status ?? ''),
                'max_attempts'    => (int) ($r->max_attempts ?? 1),
                'per_question_time_sec' => (int) ($r->per_question_time_sec ?? 30),
                'total_questions' => $totalQuestions,
                'total_points'    => (int) ($r->total_points ?? 0),
            ],
            'assignment' => [
                'id'             => $r->assignment_id ? (int) $r->assignment_id : null,
                'uuid'           => (string) ($r->assignment_uuid ?? ''),
                'assignment_code'=> (string) ($r->assignment_code ?? ''),
                'status'         => (string) ($r->assignment_status ?? ''),
                'assigned_at'    => $r->assigned_at ?? null,
            ],
            'attempt' => [
                'status' => 'submitted', // you don't store attempt_status in schema
            ],
            'result' => [
                'id'               => (int) ($r->result_id ?? 0),
                'uuid'             => (string) ($r->result_uuid ?? ''),
                'attempt_no'       => (int) ($r->attempt_no ?? 0),
                'score'            => $score,
                'accuracy'         => $accuracy, // computed
                'created_at'       => $r->result_created_at ?? null,
                'updated_at'       => $r->result_updated_at ?? null,
                'user_answer_json' => $answers,
            ],
        ];
    })->values();
}
public function submit(Request $request)
{
    // ✅ actor() is token-safe (CheckRole middleware fills these)
    $actor  = $this->actor($request);
    $userId = (int) ($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to resolve user from token (actor id missing).'
        ], 403);
    }

    // ✅ Optional: restrict who can submit
    // if ($resp = $this->requireRole($request, ['student'])) return $resp;

    // ✅ Validate request
    $validator = Validator::make($request->all(), [
        'game_uuid' => ['required', 'uuid'],

        // answers = list of question interactions
        'answers' => ['required', 'array', 'min:1'],
        'answers.*.question_uuid' => ['required', 'uuid'],
        'answers.*.selected' => ['nullable'],

        // Optional scoring/time fields saved into JSON
        'answers.*.is_correct' => ['nullable', Rule::in(['yes','no'])],
        'answers.*.spent_time_sec' => ['nullable', 'integer', 'min:0'],
        'answers.*.is_skipped' => ['nullable', Rule::in(['yes','no'])],
        'answers.*.selected_row_json' => ['nullable'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // ✅ Load game
    $game = DB::table('bubble_game')
        ->where('uuid', $request->game_uuid)
        ->whereNull('deleted_at')
        ->first();

    if (!$game) {
        return response()->json([
            'success' => false,
            'message' => 'Game not found'
        ], 404);
    }

    $answers = $request->input('answers', []);

    // ✅ Validate questions belong to THIS game
    $questionUuids = array_values(array_unique(array_filter(array_map(
        fn ($a) => $a['question_uuid'] ?? null,
        $answers
    ))));

    $questionMap = DB::table('bubble_game_questions')
        ->where('bubble_game_id', (int) $game->id)
        ->whereIn('uuid', $questionUuids)
        ->pluck('id', 'uuid'); // [uuid => id]

    $missing = array_values(array_diff($questionUuids, $questionMap->keys()->all()));
    if (!empty($missing)) {
        return response()->json([
            'success' => false,
            'message' => 'Some questions are invalid / not in this game',
            'missing_question_uuids' => $missing
        ], 422);
    }

    try {
        return DB::transaction(function () use ($request, $userId, $game, $answers, $questionMap) {

            // ✅ Lock to prevent 2 submits creating same attempt_no
            $currentMaxAttempt = (int) (DB::table('bubble_game_results')
                ->where('bubble_game_id', (int) $game->id)
                ->where('user_id', (int) $userId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->max('attempt_no') ?? 0);

            $nextAttempt = $currentMaxAttempt + 1;
            $maxAttempts = (int) ($game->max_attempts ?? 1);

            // ✅ IMPORTANT RULE:
            // nextAttempt must be <= game.max_attempts
            if ($nextAttempt > $maxAttempts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum attempts reached for this game',
                    'max_attempts' => $maxAttempts,
                    'current_attempt' => $currentMaxAttempt,
                ], 403);
            }

            // ✅ Build JSON snapshot (stored in bubble_game_results.user_answer_json)
            $normalized = [];
            foreach ($answers as $a) {
                $qUuid = (string) ($a['question_uuid'] ?? '');

                $normalized[] = [
                    'question_id'       => (int) $questionMap->get($qUuid),
                    'question_uuid'     => $qUuid,
                    'selected'          => $a['selected'] ?? null,
                    'is_correct'        => $a['is_correct'] ?? null,
                    'spent_time_sec'    => $a['spent_time_sec'] ?? null,
                    'is_skipped'        => $a['is_skipped'] ?? null,
                    'selected_row_json' => $a['selected_row_json'] ?? null,
                ];
            }

            // ✅ Always calculate score (no "first row null" bug)
            $score = $this->calculateScore(
                $normalized,
                (int) ($game->points_correct ?? 1),
                (int) ($game->points_wrong ?? 0)
            );

            $resultUuid = (string) Str::uuid();

            $resultId = DB::table('bubble_game_results')->insertGetId([
                'uuid'             => $resultUuid,
                'bubble_game_id'   => (int) $game->id,
                'user_id'          => (int) $userId,
                'attempt_no'       => (int) $nextAttempt,
                'user_answer_json' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                'score'            => (int) $score,

                'created_at'       => now(),
                'updated_at'       => now(),
                'created_at_ip'    => $request->ip(),
                'updated_at_ip'    => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Submitted successfully',
                'data' => [
                    'id'           => (int) $resultId,
                    'uuid'         => (string) $resultUuid,
                    'attempt_no'   => (int) $nextAttempt,
                    'score'        => (int) $score,
                    'max_attempts' => (int) $maxAttempts,
                ]
            ], 201);
        });

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Submit failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}


    /**
     * Display results for a specific game.
     */
    public function gameResults(Request $request, string $gameUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $query = DB::table('bubble_game_results')
            ->leftJoin('users', 'bubble_game_results.user_id', '=', 'users.id')
            ->select(
                'bubble_game_results.*',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->where('bubble_game_results.bubble_game_id', $bubbleGame->id)
            ->whereNull('bubble_game_results.deleted_at');

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('bubble_game_results.user_id', $request->user_id);
        }

        // Get only current user's results
        if ($request->get('my_results', false)) {
            $query->where('bubble_game_results.user_id', Auth::id());
        }

        // Order by
        $orderBy = $request->get('order_by', 'bubble_game_results.score');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $results = $query->offset($offset)->limit($perPage)->get();

        // Decode JSON fields
        foreach ($results as $result) {
            $result->user_answer_json = json_decode($result->user_answer_json);
        }

        return response()->json([
            'success' => true,
            'game' => $bubbleGame,
            'data' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ]);
    }

    /**
     * Store a newly created result (submit game attempt).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bubble_game_uuid' => 'required|string',
            'user_answer_json' => 'required|array',
            'user_answer_json.*.is_correct' => 'required|in:yes,no',
            'user_answer_json.*.spent_time_sec' => 'required|integer|min:0',
            'user_answer_json.*.is_skipped' => 'required|in:yes,no',
            'user_answer_json.*.selected_row_json' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get bubble game
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $request->bubble_game_uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $userId = Auth::id();

        // Check max attempts
        $attemptCount = DB::table('bubble_game_results')
            ->where('bubble_game_id', $bubbleGame->id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->count();

        if ($attemptCount >= $bubbleGame->max_attempts) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum attempts reached for this game',
                'max_attempts' => $bubbleGame->max_attempts,
                'current_attempts' => $attemptCount
            ], 403);
        }

        // Calculate score
        $score = $this->calculateScore(
            $request->user_answer_json,
            $bubbleGame->points_correct ?? 1,
            $bubbleGame->points_wrong ?? 0
        );

        // Insert result
        $resultId = DB::table('bubble_game_results')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'bubble_game_id' => $bubbleGame->id,
            'user_id' => $userId,
            'attempt_no' => $attemptCount + 1,
            'user_answer_json' => json_encode($request->user_answer_json),
            'score' => $score,
            'created_at_ip' => $request->ip(),
            'updated_at_ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Get created result
        $result = DB::table('bubble_game_results')
            ->where('id', $resultId)
            ->first();

        $result->user_answer_json = json_decode($result->user_answer_json);

        return response()->json([
            'success' => true,
            'message' => 'Game result submitted successfully',
            'data' => $result
        ], 201);
    }

    /**
     * Display the specified result.
     */
    public function show(string $uuid)
    {
        $result = DB::table('bubble_game_results')
            ->leftJoin('bubble_game', 'bubble_game_results.bubble_game_id', '=', 'bubble_game.id')
            ->leftJoin('users', 'bubble_game_results.user_id', '=', 'users.id')
            ->select(
                'bubble_game_results.*',
                'bubble_game.title as game_title',
                'bubble_game.uuid as game_uuid',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->where('bubble_game_results.uuid', $uuid)
            ->whereNull('bubble_game_results.deleted_at')
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found'
            ], 404);
        }

        $result->user_answer_json = json_decode($result->user_answer_json);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Update the specified result.
     */
    public function update(Request $request, string $uuid)
    {
        $result = DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_answer_json' => 'sometimes|array',
            'user_answer_json.*.is_correct' => 'required_with:user_answer_json|in:yes,no',
            'user_answer_json.*.spent_time_sec' => 'required_with:user_answer_json|integer|min:0',
            'user_answer_json.*.is_skipped' => 'required_with:user_answer_json|in:yes,no',
            'user_answer_json.*.selected_row_json' => 'nullable|string',
            'score' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = [
            'updated_at_ip' => $request->ip(),
            'updated_at' => now()
        ];

        if ($request->has('user_answer_json')) {
            $updateData['user_answer_json'] = json_encode($request->user_answer_json);
            
            // Recalculate score if answers changed
            $bubbleGame = DB::table('bubble_game')
                ->where('id', $result->bubble_game_id)
                ->first();
            
            $updateData['score'] = $this->calculateScore(
                $request->user_answer_json,
                $bubbleGame->points_correct ?? 1,
                $bubbleGame->points_wrong ?? 0
            );
        }

        if ($request->has('score')) {
            $updateData['score'] = $request->score;
        }

        DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->update($updateData);

        // Get updated result
        $updatedResult = DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->first();

        $updatedResult->user_answer_json = json_decode($updatedResult->user_answer_json);

        return response()->json([
            'success' => true,
            'message' => 'Result updated successfully',
            'data' => $updatedResult
        ]);
    }

    /**
     * Remove the specified result from storage (soft delete).
     */
    public function destroy(string $uuid)
    {
        $result = DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found'
            ], 404);
        }

        DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->update(['deleted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Result deleted successfully'
        ]);
    }

    /**
     * Restore a soft deleted result.
     */
    public function restore(string $uuid)
    {
        $result = DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->whereNotNull('deleted_at')
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found or not deleted'
            ], 404);
        }

        DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->update(['deleted_at' => null]);

        $restoredResult = DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->first();

        $restoredResult->user_answer_json = json_decode($restoredResult->user_answer_json);

        return response()->json([
            'success' => true,
            'message' => 'Result restored successfully',
            'data' => $restoredResult
        ]);
    }

    /**
     * Permanently delete a result.
     */
    public function forceDelete(string $uuid)
    {
        $result = DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found'
            ], 404);
        }

        DB::table('bubble_game_results')
            ->where('uuid', $uuid)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Result permanently deleted'
        ]);
    }

    /**
     * Get user statistics for a game.
     */
    public function userStats(string $gameUuid, int $userId = null)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $userId = $userId ?? Auth::id();

        $stats = DB::table('bubble_game_results')
            ->where('bubble_game_id', $bubbleGame->id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('MAX(score) as best_score'),
                DB::raw('AVG(score) as average_score'),
                DB::raw('MIN(score) as lowest_score'),
                DB::raw('SUM(score) as total_score')
            )
            ->first();

        $attempts = DB::table('bubble_game_results')
            ->where('bubble_game_id', $bubbleGame->id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($attempts as $attempt) {
            $attempt->user_answer_json = json_decode($attempt->user_answer_json);
        }

        return response()->json([
            'success' => true,
            'game' => $bubbleGame,
            'statistics' => $stats,
            'attempts' => $attempts,
            'remaining_attempts' => max(0, $bubbleGame->max_attempts - $stats->total_attempts)
        ]);
    }

    /**
     * Get leaderboard for a game.
     */
    public function leaderboard(Request $request, string $gameUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $limit = $request->get('limit', 10);

        $leaderboard = DB::table('bubble_game_results')
            ->leftJoin('users', 'bubble_game_results.user_id', '=', 'users.id')
            ->select(
                'bubble_game_results.user_id',
                'users.name as user_name',
                'users.email as user_email',
                DB::raw('MAX(bubble_game_results.score) as best_score'),
                DB::raw('COUNT(*) as total_attempts'),
                DB::raw('AVG(bubble_game_results.score) as average_score')
            )
            ->where('bubble_game_results.bubble_game_id', $bubbleGame->id)
            ->whereNull('bubble_game_results.deleted_at')
            ->groupBy('bubble_game_results.user_id', 'users.name', 'users.email')
            ->orderBy('best_score', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'game' => $bubbleGame,
            'leaderboard' => $leaderboard
        ]);
    }

    /**
     * Calculate score based on answers.
     *
     * @param array $answers
     * @param int $pointsCorrect
     * @param int $pointsWrong
     * @return int
     */
    private function calculateScore(array $answers, int $pointsCorrect = 1, int $pointsWrong = 0): int
    {
        $score = 0;

        foreach ($answers as $answer) {
            if (!is_array($answer)) continue;

            if (isset($answer['is_correct'])) {
                if ($answer['is_correct'] === 'yes') {
                    $score += $pointsCorrect;
                } else {
                    $score += $pointsWrong;
                }
            }
        }

        return max(0, $score);
    }


    /**
     * Get detailed analytics for a game.
     */
    public function analytics(string $gameUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $analytics = [
            'total_results' => DB::table('bubble_game_results')
                ->where('bubble_game_id', $bubbleGame->id)
                ->whereNull('deleted_at')
                ->count(),

            'unique_users' => DB::table('bubble_game_results')
                ->where('bubble_game_id', $bubbleGame->id)
                ->whereNull('deleted_at')
                ->distinct('user_id')
                ->count('user_id'),

            'score_stats' => DB::table('bubble_game_results')
                ->where('bubble_game_id', $bubbleGame->id)
                ->whereNull('deleted_at')
                ->select(
                    DB::raw('MAX(score) as highest_score'),
                    DB::raw('AVG(score) as average_score'),
                    DB::raw('MIN(score) as lowest_score')
                )
                ->first(),

            'completion_rate' => DB::table('bubble_game_results')
                ->where('bubble_game_id', $bubbleGame->id)
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN score > 0 THEN 1 ELSE 0 END) as completed
                ')
                ->first(),

            'recent_activity' => DB::table('bubble_game_results')
                ->leftJoin('users', 'bubble_game_results.user_id', '=', 'users.id')
                ->select(
                    'bubble_game_results.*',
                    'users.name as user_name'
                )
                ->where('bubble_game_results.bubble_game_id', $bubbleGame->id)
                ->whereNull('bubble_game_results.deleted_at')
                ->orderBy('bubble_game_results.created_at', 'desc')
                ->limit(10)
                ->get()
        ];

        foreach ($analytics['recent_activity'] as $activity) {
            $activity->user_answer_json = json_decode($activity->user_answer_json);
        }

        return response()->json([
            'success' => true,
            'game' => $bubbleGame,
            'analytics' => $analytics
        ]);
    }
private function jsonSafe($val, $default = null)
{
    if ($default === null) $default = [];
    if ($val === null || $val === '') return $default;
    if (is_array($val)) return $val;

    try {
        $decoded = json_decode($val, true);
        return (json_last_error() === JSON_ERROR_NONE) ? ($decoded ?? $default) : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}

/**
 * Accepts id or uuid.
 */
private function applyIdOrUuidWhere($q, string $colId, string $colUuid, string $key): void
{
    $q->where(function ($w) use ($colId, $colUuid, $key) {
        if (ctype_digit($key)) $w->where($colId, (int)$key);
        else $w->where($colUuid, $key);
    });
}

/**
 * Bubble game lookup by id/uuid.
 */
private function gameByKeyFixed(string $gameKey)
{
    $q = DB::table('bubble_game')->whereNull('deleted_at');
    $this->applyIdOrUuidWhere($q, 'id', 'uuid', $gameKey);
    return $q->first();
}
public function resultDetail(Request $request, string $resultKey)
    {
        if ($resp = $this->requireRole($request, ['student','admin','super_admin'])) return $resp;

        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        if ($userId <= 0) {
            return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
        }

        $q = DB::table('bubble_game_results as r')
            ->join('bubble_game as g', 'g.id', '=', 'r.bubble_game_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereNull('r.deleted_at')
            ->whereNull('g.deleted_at')
            ->select([
                'r.id as result_id',
                'r.uuid as result_uuid',
                'r.bubble_game_id',
                'r.user_id',
                'r.attempt_no',
                'r.score',
                'r.user_answer_json',
                'r.created_at as result_created_at',

                'g.id as game_id',
                'g.uuid as game_uuid',
                'g.title as game_title',
                'g.description as game_description',
                'g.max_attempts',
                'g.per_question_time_sec',
                'g.allow_skip',
                'g.points_correct',
                'g.points_wrong',
                'g.show_solution_after',

                'u.uuid as student_uuid',
                'u.name as student_name',
                'u.email as student_email',
            ]);

        $this->applyIdOrUuidWhere($q, 'r.id', 'r.uuid', $resultKey);

        $row = $q->first();

        if (!$row) {
            return response()->json(['success'=>false,'message'=>'Result not found'], 404);
        }

        // Student ownership guard
        if ($role === 'student' && (int)$row->user_id !== $userId) {
            return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
        }

        // Decode snapshot from bubble_game_results.user_answer_json
        $snapshot = $this->jsonSafe($row->user_answer_json, []);
        $snapByUuid = [];
        if (is_array($snapshot)) {
            foreach ($snapshot as $s) {
                if (is_array($s) && !empty($s['question_uuid'])) {
                    $snapByUuid[(string)$s['question_uuid']] = $s;
                }
            }
        }

        // Load questions
        $questions = DB::table('bubble_game_questions as q')
            ->where('q.bubble_game_id', (int)$row->game_id)
            ->where('q.status', 'active')
            ->orderBy('q.order_no')
            ->select([
                'q.id','q.uuid','q.order_no','q.title','q.select_type',
                'q.bubbles_json','q.answer_sequence_json','q.answer_value_json',
                'q.bubbles_count','q.points','q.status'
            ])
            ->get();

        $questionPayload = [];

        foreach ($questions as $qRow) {
            $qUuid = (string)$qRow->uuid;
            $snap  = $snapByUuid[$qUuid] ?? null;

            // ✅ Decode bubbles + sequence
            $bubbles = $this->jsonSafe($qRow->bubbles_json, []);
            $seq     = $this->jsonSafe($qRow->answer_sequence_json, []);

            // ✅ Build correct order labels based on answer_sequence_json indexes
            $correctOrder = [];
            if (is_array($seq) && is_array($bubbles)) {
                foreach ($seq as $idx) {
                    $i = is_numeric($idx) ? (int)$idx : null;
                    if ($i !== null && isset($bubbles[$i])) {
                        $label = $bubbles[$i]['label'] ?? null;
                        if ($label !== null && $label !== '') {
                            $correctOrder[] = $label;
                        }
                    }
                }
            }

            // ✅ Build your order from selected_row_json (can be JSON string)
            $yourOrder = null;
            if (is_array($snap) && array_key_exists('selected_row_json', $snap) && $snap['selected_row_json'] !== null) {
                $yourOrder = $this->jsonSafe($snap['selected_row_json'], null);
            }
            // Fallback if older snapshot uses "selected" as array
            if ($yourOrder === null && is_array($snap) && isset($snap['selected']) && is_array($snap['selected'])) {
                $yourOrder = $snap['selected'];
            }

            $questionPayload[] = [
                'question_id'   => (int)$qRow->id,
                'question_uuid' => $qUuid,
                'order_no'      => (int)$qRow->order_no,
                'title'         => (string)($qRow->title ?? ''),
                'select_type'   => (string)($qRow->select_type ?? 'ascending'),
                'bubbles_count' => (int)($qRow->bubbles_count ?? 0),
                'points'        => (int)($qRow->points ?? 1),

                // original decoded payload
                'bubbles_json'          => $bubbles,
                'correct_sequence_json' => is_array($seq) ? $seq : $this->jsonSafe($qRow->answer_sequence_json, null),
                'correct_value_json'    => $this->jsonSafe($qRow->answer_value_json, null),

                // ✅ NEW fields used by frontend
                'correct_order' => $correctOrder, // labels in correct order
                'your_order'    => $yourOrder,    // labels in user's tapped order

                // snapshot fields
                'selected'       => is_array($snap) ? ($snap['selected'] ?? null) : null,
                'is_correct'     => is_array($snap) ? ($snap['is_correct'] ?? null) : null,
                'spent_time_sec' => is_array($snap) ? ($snap['spent_time_sec'] ?? null) : null,
                'is_skipped'     => is_array($snap) ? ($snap['is_skipped'] ?? null) : null,

                // keep but make consistent (decoded)
                'selected_row_json' => $yourOrder,
            ];
        }

        // If you DON’T have bubble_game_attempts table, keep this null
        $attempt = null;
        if (Schema::hasTable('bubble_game_attempts')) {
            $attemptRow = DB::table('bubble_game_attempts')
                ->where('bubble_game_id', (int)$row->game_id)
                ->where('user_id', (int)$row->user_id)
                ->orderByDesc('id')
                ->first();

            if ($attemptRow) {
                $attempt = [
                    'id' => (int)$attemptRow->id,
                    'status' => (string)($attemptRow->status ?? ''),
                    'started_at' => !empty($attemptRow->started_at)
                        ? Carbon::parse($attemptRow->started_at)->toDateTimeString()
                        : null,
                    'submitted_at' => !empty($attemptRow->submitted_at)
                        ? Carbon::parse($attemptRow->submitted_at)->toDateTimeString()
                        : null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'game' => [
                'id' => (int)$row->game_id,
                'uuid' => (string)$row->game_uuid,
                'title' => (string)$row->game_title,
                'description' => (string)($row->game_description ?? ''),
                'max_attempts' => (int)($row->max_attempts ?? 1),
                'per_question_time_sec' => (int)($row->per_question_time_sec ?? 0),
                'allow_skip' => (string)($row->allow_skip ?? 'no'),
            ],
            'attempt' => $attempt,
            'result' => [
                'result_id' => (int)$row->result_id,
                'result_uuid' => (string)$row->result_uuid,
                'user_id' => (int)$row->user_id,
                'attempt_no' => (int)$row->attempt_no,
                'score' => (int)$row->score,
                'result_created_at' => $row->result_created_at
                    ? Carbon::parse($row->result_created_at)->toDateTimeString()
                    : null,
            ],
            'student' => [
                'id' => (int)$row->user_id,
                'uuid' => (string)($row->student_uuid ?? ''),
                'name' => (string)$row->student_name,
                'email' => (string)$row->student_email,
            ],
            'questions' => $questionPayload,
        ], 200);
    }
public function resultDetailForInstructor(Request $request, string $resultKey)
{
    // if ($resp = $this->requireRole($request, ['instructor','examiner','admin','super_admin'])) return $resp;

    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
    }

    $q = DB::table('bubble_game_results as r')
        ->join('bubble_game as g', 'g.id', '=', 'r.bubble_game_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->whereNull('r.deleted_at')
        ->whereNull('g.deleted_at')
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.bubble_game_id',
            'r.user_id',
            'r.attempt_no',
            'r.score',
            'r.user_answer_json',
            'r.created_at as result_created_at',

            'g.id as game_id',
            'g.uuid as game_uuid',
            'g.title as game_title',
            'g.description as game_description',
            'g.per_question_time_sec',
            'g.allow_skip',

            'u.uuid as student_uuid',
            'u.name as student_name',
            'u.email as student_email',
        ]);

    $this->applyIdOrUuidWhere($q, 'r.id', 'r.uuid', $resultKey);
    $row = $q->first();

    if (!$row) {
        return response()->json(['success'=>false,'message'=>'Result not found'], 404);
    }

    // ✅ Correct instructor assignment guard (optional)
    if (in_array($role, ['instructor','examiner'], true)) {
        if (!$this->userAssignedToBubbleGame($userId, (int)$row->game_id)) {
            return response()->json(['success'=>false,'message'=>'You are not assigned to this bubble game'], 403);
        }
    }

    // Decode snapshot
    $snapshot = $this->jsonSafe($row->user_answer_json, []);
    $snapByUuid = [];
    foreach ($snapshot as $s) if (!empty($s['question_uuid'])) $snapByUuid[(string)$s['question_uuid']] = $s;

    $questions = DB::table('bubble_game_questions as q')
        ->where('q.bubble_game_id', (int)$row->game_id)
        ->orderBy('q.order_no')
        ->select([
            'q.id','q.uuid','q.order_no','q.title','q.select_type',
            'q.bubbles_json','q.answer_sequence_json','q.answer_value_json',
            'q.bubbles_count','q.points','q.status'
        ])
        ->get();

    $questionPayload = [];
    foreach ($questions as $q) {
        $qUuid = (string)$q->uuid;
        $snap  = $snapByUuid[$qUuid] ?? null;

        $questionPayload[] = [
            'question_id' => (int)$q->id,
            'question_uuid' => $qUuid,
            'order_no' => (int)$q->order_no,
            'title' => (string)($q->title ?? ''),
            'select_type' => (string)($q->select_type ?? 'ascending'),
            'bubbles_json' => $this->jsonSafe($q->bubbles_json, []),
            'correct_sequence_json' => $this->jsonSafe($q->answer_sequence_json, null),
            'correct_value_json' => $this->jsonSafe($q->answer_value_json, null),

            'selected' => $snap['selected'] ?? null,
            'is_correct' => $snap['is_correct'] ?? null,
            'spent_time_sec' => $snap['spent_time_sec'] ?? null,
            'is_skipped' => $snap['is_skipped'] ?? null,
        ];
    }

    return response()->json([
        'success' => true,
        'game' => [
            'id' => (int)$row->game_id,
            'uuid' => (string)$row->game_uuid,
            'title' => (string)$row->game_title,
            'description' => (string)($row->game_description ?? ''),
        ],
        'result' => [
            'result_id' => (int)$row->result_id,
            'result_uuid' => (string)$row->result_uuid,
            'score' => (int)$row->score,
            'attempt_no' => (int)$row->attempt_no,
            'result_created_at' => $row->result_created_at ? Carbon::parse($row->result_created_at)->toDateTimeString() : null,
        ],
        'student' => [
            'id' => (int)$row->user_id,
            'uuid' => (string)($row->student_uuid ?? ''),
            'name' => (string)$row->student_name,
            'email' => (string)$row->student_email,
        ],
        'questions' => $questionPayload,
    ], 200);
}
public function assignedResultsForGame(Request $request, string $gameKey)
{
    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
    }

    $game = $this->gameByKeyFixed($gameKey);
    if (!$game) {
        return response()->json(['success'=>false,'message'=>'Bubble game not found'], 404);
    }

    // ✅ examiner/instructor can only view if assigned (you wanted this)
    if (in_array($role, ['instructor','examiner'], true)) {
        if (!$this->userAssignedToBubbleGame($userId, (int)$game->id)) {
            return response()->json(['success'=>false,'message'=>'You are not assigned to this bubble game'], 403);
        }
    }

    // -----------------------------
    // Assigned STUDENTS for this game
    // (your assignments table can contain examiner too, so we filter users.role if exists)
    // -----------------------------
    $assignedUsersQ = DB::table('user_bubble_game_assignments as a')
        ->join('users as u', 'u.id', '=', 'a.user_id')
        ->where('a.bubble_game_id', (int)$game->id)
        ->whereNull('a.deleted_at')
        ->where('a.status', 'active');

    // If users table has role column, restrict to student only
    try {
        if (Schema::hasColumn('users', 'role')) {
            $assignedUsersQ->whereRaw("LOWER(u.role) = 'student'");
        }
    } catch (\Throwable $e) {}

    $assignedStudentIds = $assignedUsersQ->pluck('u.id')->map(fn($x)=>(int)$x)->values()->all();
    $totalAssignedStudents = count($assignedStudentIds);

    // -----------------------------
    // Question stats (for accuracy%)
    // -----------------------------
    $qStats = DB::table('bubble_game_questions as bq')
        ->selectRaw('bq.bubble_game_id, COUNT(*) as total_questions, COALESCE(SUM(bq.points),0) as total_points')
        ->where('bq.status', 'active')
        ->groupBy('bq.bubble_game_id');

    // -----------------------------
    // Attempts list (THIS is what your frontend uses)
    // -----------------------------
    $attemptsQ = DB::table('bubble_game_results as r')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->join('bubble_game as g', 'g.id', '=', 'r.bubble_game_id')
        ->leftJoinSub($qStats, 'qs', function($j){
            $j->on('qs.bubble_game_id', '=', 'g.id');
        })
        ->where('r.bubble_game_id', (int)$game->id)
        ->whereNull('r.deleted_at')
        ->whereNull('g.deleted_at');

    // Only assigned students should be listed
    if ($totalAssignedStudents > 0) {
        $attemptsQ->whereIn('r.user_id', $assignedStudentIds);
    } else {
        // no assigned students => no attempts
        $attemptsQ->whereRaw('1=0');
    }

    // Search filter (name/email)
    $qText = trim((string)$request->query('q', ''));
    if ($qText !== '') {
        $attemptsQ->where(function($w) use ($qText){
            $w->where('u.name', 'like', "%{$qText}%")
              ->orWhere('u.email','like', "%{$qText}%");
        });
    }

    // Order newest first by default (frontend sorts after normalization anyway)
    $attemptsQ->orderByDesc('r.created_at');

    $attempts = $attemptsQ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.user_id as student_id',
            'u.name as student_name',
            'u.email as student_email',
            'r.attempt_no',
            'r.score',
            'r.created_at as result_created_at',
            DB::raw('COALESCE(qs.total_questions,0) as total_questions'),
            DB::raw('COALESCE(qs.total_points,0) as total_points'),
        ])
        ->get()
        ->map(function($a){
            $score = (int)($a->score ?? 0);
            $den   = (int)($a->total_points ?? 0);
            if ($den <= 0) $den = (int)($a->total_questions ?? 0);
            $acc = ($den > 0) ? round(($score / $den) * 100, 2) : null;

            return [
                'result_id'         => (int)$a->result_id,
                'result_uuid'       => (string)($a->result_uuid ?? ''),
                'student_id'        => (int)$a->student_id,
                'student_name'      => (string)($a->student_name ?? ''),
                'student_email'     => (string)($a->student_email ?? ''),
                'attempt_no'        => (int)($a->attempt_no ?? 1),
                'score'             => $score,
                'accuracy'          => $acc, // ✅ frontend uses this as percentage
                'result_created_at' => $a->result_created_at ? Carbon::parse($a->result_created_at)->toDateTimeString() : null,
            ];
        })
        ->values();

    // -----------------------------
    // Stats (frontend reads these too)
    // -----------------------------
    $totalAttempts   = $attempts->count();
    $uniqueAttempted = $attempts->pluck('student_id')->unique()->count();

    $avgScore = $totalAttempts ? round($attempts->avg('score'), 2) : null;
    $avgPct   = $totalAttempts ? round($attempts->avg('accuracy'), 2) : null;

    return response()->json([
        'success' => true,
        'data' => [
            'game' => [
                'id'    => (int)$game->id,
                'uuid'  => (string)$game->uuid,
                'title' => (string)$game->title,
                'total_time_minutes' => isset($game->total_time_minutes) ? (int)$game->total_time_minutes : null,
                'pass_percentage' => isset($game->pass_percentage) ? (float)$game->pass_percentage : 40, // optional
            ],
            'stats' => [
                'total_attempts'          => (int)$totalAttempts,
                'unique_attempted'        => (int)$uniqueAttempted,
                'total_assigned_students' => (int)$totalAssignedStudents,
                'avg_score'               => $avgScore,
                'avg_percentage'          => $avgPct,
            ],
            'attempts' => $attempts,
        ]
    ], 200);
}

private function userAssignedToBubbleGame(int $userId, int $gameId): bool
{
    return DB::table('user_bubble_game_assignments')
        ->where('user_id', $userId)
        ->where('bubble_game_id', $gameId)
        ->whereNull('deleted_at')
        ->where('status', 'active')
        ->exists();
}
/**
 * EXPORT Bubble Game Result (DOCX preferred, fallback HTML)
 * GET /api/bubble-game-results/export/{resultKey}?format=docx|html
 *
 * - student: can export ONLY own result
 * - admin/super_admin: can export any
 * - instructor/examiner: (optional) assignment check (TEMP uses user_bubble_game_assignments)
 */
public function export(Request $request, string $resultKey)
{
    if ($resp = $this->requireRole($request, ['student','instructor','examiner','admin','super_admin'])) {
        return $resp;
    }

    $actor = $this->actor($request);
    $role  = $this->normalizeRole($actor['role'] ?? '');
    $userId= (int) ($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
    }

    // ---------- 1) Load result + game + student ----------
    $row = DB::table('bubble_game_results as r')
        ->join('bubble_game as g', 'g.id', '=', 'r.bubble_game_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->whereNull('r.deleted_at')
        ->whereNull('g.deleted_at')
        ->where(function ($w) use ($resultKey) {
            if (is_numeric($resultKey)) {
                $w->where('r.id', (int)$resultKey);
            } else {
                $w->where('r.uuid', (string)$resultKey);
            }
        })
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.user_id',
            'r.bubble_game_id',
            'r.attempt_no',
            'r.score',
            'r.user_answer_json',
            'r.created_at as result_created_at',

            'g.id as game_id',
            'g.uuid as game_uuid',
            'g.title as game_title',
            'g.description as game_description',
            'g.max_attempts',
            'g.per_question_time_sec',
            'g.allow_skip',

            'u.name as student_name',
            'u.email as student_email',
        ])
        ->first();

    if (!$row) {
        return response()->json(['success'=>false,'message'=>'Result not found'], 404);
    }

    // ---------- 2) Ownership / assignment guards ----------
    if ($role === 'student' && (int)$row->user_id !== $userId) {
        return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
    }

    // TEMP assignment check for instructor/examiner (remove if you don't want)
    if (in_array($role, ['instructor','examiner'], true)) {
        $assigned = DB::table('user_bubble_game_assignments')
            ->where('bubble_game_id', (int)$row->game_id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->exists();

        if (!$assigned) {
            return response()->json(['success'=>false,'message'=>'You are not assigned to this bubble game'], 403);
        }
    }

    // ---------- 3) Decode snapshot ----------
    try {
        $snapshot = json_decode($row->user_answer_json ?? '[]', true) ?: [];
    } catch (\Throwable $e) {
        $snapshot = [];
    }

    $snapByUuid = [];
    foreach ($snapshot as $s) {
        if (!empty($s['question_uuid'])) $snapByUuid[(string)$s['question_uuid']] = $s;
    }

    // ---------- 4) Load questions ----------
    $questions = DB::table('bubble_game_questions')
        ->where('bubble_game_id', (int)$row->game_id)
        ->orderBy('order_no')
        ->get();

    // Build export rows
    $items = [];
    $totalCorrect = 0;
    $totalWrong   = 0;
    $totalSkipped = 0;

    foreach ($questions as $q) {
        $qUuid = (string)$q->uuid;
        $snap  = $snapByUuid[$qUuid] ?? null;

        $isCorrect = ($snap['is_correct'] ?? null) === 'yes';
        $isSkipped = ($snap['is_skipped'] ?? null) === 'yes';

        if ($isSkipped) $totalSkipped++;
        else if ($isCorrect) $totalCorrect++;
        else if (($snap['is_correct'] ?? null) !== null) $totalWrong++;

        $correctSeq = $q->answer_sequence_json ? json_decode($q->answer_sequence_json, true) : null;
        $selected   = $snap['selected'] ?? null;

        $items[] = [
            'no'            => (int)($q->order_no ?? 0),
            'question'      => (string)($q->title ?? ''),
            'type'          => (string)($q->select_type ?? ''),
            'your_answer'   => is_array($selected) ? json_encode($selected, JSON_UNESCAPED_UNICODE) : (string)($selected ?? '—'),
            'correct_order' => $correctSeq !== null
                                ? (is_array($correctSeq) ? json_encode($correctSeq, JSON_UNESCAPED_UNICODE) : (string)$correctSeq)
                                : '—',
            'is_correct'    => $snap['is_correct'] ?? null,
            'is_skipped'    => $snap['is_skipped'] ?? null,
            'spent_time_sec'=> $snap['spent_time_sec'] ?? null,
        ];
    }

    $format = strtolower((string)$request->query('format', 'docx'));
    $safeName = 'bubble_game_result_'.$row->result_id;

    // ---------- 5) DOCX via PhpWord if available ----------
    if ($format === 'docx') {
        if (class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            $section->addText('Bubble Game Result', ['bold'=>true,'size'=>16]);
            $section->addTextBreak(1);

            $section->addText('Game: '.$row->game_title, ['bold'=>true]);
            $section->addText('Student: '.$row->student_name.' ('.$row->student_email.')', ['size'=>11]);
            $section->addText('Attempt #: '.$row->attempt_no, ['size'=>11]);
            $section->addText('Score: '.$row->score, ['size'=>11]);
            $section->addText('Result At: '.($row->result_created_at ? \Carbon\Carbon::parse($row->result_created_at)->toDayDateTimeString() : '—'), ['size'=>11]);
            $section->addTextBreak(1);

            $section->addText("Correct: {$totalCorrect}   Wrong: {$totalWrong}   Skipped: {$totalSkipped}", ['size'=>11]);
            $section->addTextBreak(1);

            $table = $section->addTable(['borderSize'=>6,'borderColor'=>'cccccc','cellMargin'=>60]);
            $table->addRow();
            foreach (['Q#','Question','Your Answer','Correct Order','Correct?','Skipped?','Time(sec)'] as $col) {
                $table->addCell(2000)->addText($col, ['bold'=>true]);
            }

            foreach ($items as $it) {
                $table->addRow();
                $table->addCell(700)->addText((string)$it['no']);
                $table->addCell(6000)->addText(strip_tags($it['question']));
                $table->addCell(4000)->addText($it['your_answer'] !== '' ? $it['your_answer'] : '—');
                $table->addCell(4000)->addText($it['correct_order'] !== '' ? $it['correct_order'] : '—');
                $table->addCell(1400)->addText((string)($it['is_correct'] ?? '—'));
                $table->addCell(1400)->addText((string)($it['is_skipped'] ?? '—'));
                $table->addCell(1400)->addText($it['spent_time_sec'] !== null ? (string)$it['spent_time_sec'] : '—');
            }

            $tmp = tempnam(sys_get_temp_dir(), 'bgr_').'.docx';
            $phpWord->save($tmp, 'Word2007');

            return response()->download($tmp, $safeName.'.docx')->deleteFileAfterSend(true);
        }

        // fallback to HTML if PhpWord not installed
        $format = 'html';
    }

    // ---------- 6) HTML fallback (Word-compatible if you want) ----------
    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'.$safeName.'</title>
    <style>
      body{font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#111}
      h1{font-size:18px;margin:0 0 8px}
      .meta{margin:0 0 10px}
      table{border-collapse:collapse;width:100%}
      th,td{border:1px solid #ccc;padding:6px;vertical-align:top}
      th{background:#f5f5f5}
      .muted{color:#555}
    </style></head><body>';

    $html .= '<h1>Bubble Game Result</h1>';
    $html .= '<div class="meta"><b>Game:</b> '.htmlspecialchars($row->game_title).'<br>';
    $html .= '<b>Student:</b> '.htmlspecialchars($row->student_name).' ('.htmlspecialchars($row->student_email).')<br>';
    $html .= '<b>Attempt #:</b> '.(int)$row->attempt_no.'<br>';
    $html .= '<b>Score:</b> '.(int)$row->score.'<br>';
    $html .= '<b>Result At:</b> '.($row->result_created_at ? htmlspecialchars(Carbon::parse($row->result_created_at)->toDayDateTimeString()) : '—').'<br>';
    $html .= '<b>Correct:</b> '.$totalCorrect.' &nbsp; <b>Wrong:</b> '.$totalWrong.' &nbsp; <b>Skipped:</b> '.$totalSkipped.'</div>';

    $html .= '<table><thead><tr>
        <th style="width:60px">Q#</th>
        <th>Question</th>
        <th>Your Answer</th>
        <th>Correct Order</th>
        <th style="width:80px">Correct?</th>
        <th style="width:80px">Skipped?</th>
        <th style="width:90px">Time(sec)</th>
      </tr></thead><tbody>';

    foreach ($items as $it) {
        $html .= '<tr>';
        $html .= '<td>'.(int)$it['no'].'</td>';
        $html .= '<td>'.htmlspecialchars(strip_tags((string)$it['question'])).'</td>';
        $html .= '<td>'.htmlspecialchars((string)($it['your_answer'] ?? '—')).'</td>';
        $html .= '<td>'.htmlspecialchars((string)($it['correct_order'] ?? '—')).'</td>';
        $html .= '<td>'.htmlspecialchars((string)($it['is_correct'] ?? '—')).'</td>';
        $html .= '<td>'.htmlspecialchars((string)($it['is_skipped'] ?? '—')).'</td>';
        $html .= '<td>'.htmlspecialchars($it['spent_time_sec'] !== null ? (string)$it['spent_time_sec'] : '—').'</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p class="muted">Exported on '.htmlspecialchars(now()->toDateTimeString()).'</p>';
    $html .= '</body></html>';

    // If you want Word download: change content-type + filename to .doc
    return response($html, 200, [
        'Content-Type' => 'text/html; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="'.$safeName.'.html"',
    ]);
}

}