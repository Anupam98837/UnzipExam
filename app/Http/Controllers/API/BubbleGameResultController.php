<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
    /**
     * Display a listing of results.
     */
    public function index(Request $request)
    {
        $query = DB::table('bubble_game_results')
            ->leftJoin('bubble_game', 'bubble_game_results.bubble_game_id', '=', 'bubble_game.id')
            ->leftJoin('users', 'bubble_game_results.user_id', '=', 'users.id')
            ->select(
                'bubble_game_results.*',
                'bubble_game.title as game_title',
                'bubble_game.uuid as game_uuid',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->whereNull('bubble_game_results.deleted_at');

        if ($request->filled('game_uuid')) {
            $query->where('bubble_game.uuid', $request->game_uuid);
        }

        if ($request->filled('user_id')) {
            $query->where('bubble_game_results.user_id', (int) $request->user_id);
        }

        if ($request->boolean('my_results')) {
            $query->where('bubble_game_results.user_id', (int) Auth::id());
        }

        if ($request->filled('attempt_no')) {
            $query->where('bubble_game_results.attempt_no', (int) $request->attempt_no);
        }

        if ($request->filled('min_score')) {
            $query->where('bubble_game_results.score', '>=', (int) $request->min_score);
        }

        if ($request->filled('max_score')) {
            $query->where('bubble_game_results.score', '<=', (int) $request->max_score);
        }

        if ($request->filled('search')) {
            $query->where('bubble_game.title', 'like', '%' . $request->search . '%');
        }

        // ✅ Whitelist ordering to avoid unsafe column injection
        $allowedOrderBy = [
            'bubble_game_results.created_at',
            'bubble_game_results.updated_at',
            'bubble_game_results.score',
            'bubble_game_results.attempt_no',
            'users.name',
            'bubble_game.title',
        ];

        $orderBy = $request->get('order_by', 'bubble_game_results.created_at');
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'bubble_game_results.created_at';
        }

        $orderDir = strtolower($request->get('order_dir', 'desc'));
        $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'desc';

        $query->orderBy($orderBy, $orderDir);

        // Paginate (manual)
        $perPage = max(1, (int) $request->get('per_page', 15));
        $page = max(1, (int) $request->get('page', 1));
        $offset = ($page - 1) * $perPage;

        $total = (clone $query)->count();
        $results = $query->offset($offset)->limit($perPage)->get();

        foreach ($results as $result) {
            $result->user_answer_json = $result->user_answer_json
                ? json_decode($result->user_answer_json, true)
                : null;
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total ? ($offset + 1) : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

public function submit(Request $request)
{
    // ✅ use actor() (your middleware fills these)
    $actor  = $this->actor($request);
    $userId = (int) ($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to resolve user from token (actor id missing).'
        ], 403);
    }

    // (Optional) restrict who can submit
    // if ($resp = $this->requireRole($request, ['student'])) return $resp;

    // ✅ validate: NO time_taken_sec here (not in your table)
    $validator = Validator::make($request->all(), [
        'game_uuid' => ['required', 'uuid'],
        'answers' => ['required', 'array', 'min:1'],
        'answers.*.question_uuid' => ['required', 'uuid'],
        'answers.*.selected' => ['nullable'],

        // Optional fields (stored inside JSON)
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

    // ✅ Validate questions belong to this game (prevents null id issues)
    $questionUuids = array_values(array_unique(array_filter(array_map(
        fn ($a) => $a['question_uuid'] ?? null,
        $answers
    ))));

    $questionMap = DB::table('bubble_game_questions')
        ->where('bubble_game_id', $game->id)
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

            // ✅ attempt_no computed only inside transaction
            $attemptNo = (int) (DB::table('bubble_game_results')
                ->where('bubble_game_id', $game->id)
                ->where('user_id', $userId)
                ->max('attempt_no') ?? 0) + 1;

            // ✅ Build JSON to store in user_answer_json (matches your migration)
            $normalized = [];
            foreach ($answers as $a) {
                $qUuid = $a['question_uuid'];

                $normalized[] = [
                    'question_id'       => (int) $questionMap->get($qUuid), // stored inside JSON only
                    'question_uuid'     => $qUuid,
                    'selected'          => $a['selected'] ?? null,
                    'is_correct'        => $a['is_correct'] ?? null,
                    'spent_time_sec'    => $a['spent_time_sec'] ?? null,
                    'is_skipped'        => $a['is_skipped'] ?? null,
                    'selected_row_json' => $a['selected_row_json'] ?? null,
                ];
            }

            // ✅ Score (only if is_correct present)
            $score = 0;
            if (!empty($normalized) && array_key_exists('is_correct', $normalized[0]) && $normalized[0]['is_correct'] !== null) {
                $score = $this->calculateScore(
                    $normalized,
                    (int) ($game->points_correct ?? 1),
                    (int) ($game->points_wrong ?? 0)
                );
            }

            $resultUuid = (string) Str::uuid();

            $resultId = DB::table('bubble_game_results')->insertGetId([
                'uuid'            => $resultUuid,
                'bubble_game_id'  => (int) $game->id,
                'user_id'         => (int) $userId,
                'attempt_no'      => (int) $attemptNo,
                'user_answer_json'=> json_encode($normalized, JSON_UNESCAPED_UNICODE),
                'score'           => (int) $score,

                'created_at'      => now(),
                'updated_at'      => now(),
                'created_at_ip'   => $request->ip(),
                'updated_at_ip'   => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Submitted successfully',
                'data' => [
                    'id' => $resultId,
                    'uuid' => $resultUuid,
                    'attempt_no' => $attemptNo,
                    'score' => (int) $score
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
}