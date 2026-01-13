<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BubbleGameResultController extends Controller
{
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

        // Filter by game UUID
        if ($request->has('game_uuid')) {
            $query->where('bubble_game.uuid', $request->game_uuid);
        }

        // Filter by user ID
        if ($request->has('user_id')) {
            $query->where('bubble_game_results.user_id', $request->user_id);
        }

        // Filter by current authenticated user
        if ($request->get('my_results', false)) {
            $query->where('bubble_game_results.user_id', Auth::id());
        }

        // Filter by attempt number
        if ($request->has('attempt_no')) {
            $query->where('bubble_game_results.attempt_no', $request->attempt_no);
        }

        // Filter by score range
        if ($request->has('min_score')) {
            $query->where('bubble_game_results.score', '>=', $request->min_score);
        }
        if ($request->has('max_score')) {
            $query->where('bubble_game_results.score', '<=', $request->max_score);
        }

        // Search by game title
        if ($request->has('search')) {
            $query->where('bubble_game.title', 'like', '%' . $request->search . '%');
        }

        // Order by
        $orderBy = $request->get('order_by', 'bubble_game_results.created_at');
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
            if (isset($answer['is_correct'])) {
                if ($answer['is_correct'] === 'yes') {
                    $score += $pointsCorrect;
                } else {
                    $score += $pointsWrong;
                }
            }
        }

        return max(0, $score); // Ensure score is never negative
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