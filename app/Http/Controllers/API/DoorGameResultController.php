<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DoorGameResultController extends Controller
{
    /**
     * Display a listing of game results
     */
    public function index(Request $request)
    {
        $query = DB::table('door_game_results as dgr')
            ->join('door_game as dg', 'dgr.door_game_id', '=', 'dg.id')
            ->join('users as u', 'dgr.user_id', '=', 'u.id')
            ->whereNull('dgr.deleted_at')
            ->select([
                'dgr.*',
                'dg.title as game_title',
                'u.name as user_name',
                'u.email as user_email'
            ]);

        // Filter by game
        if ($request->has('door_game_id')) {
            $query->where('dgr.door_game_id', $request->door_game_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('dgr.user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('dgr.status', $request->status);
        }

        // Sort by fastest time
        if ($request->input('sort') === 'fastest') {
            $query->whereNotNull('dgr.time_taken_ms')
                ->orderBy('dgr.time_taken_ms', 'asc');
        } else {
            $query->orderBy('dgr.created_at', 'desc');
        }

        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $results = $query->offset($offset)->limit($perPage)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $results,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Store a newly created game result
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'door_game_id' => 'required|integer|exists:door_game,id',
            'user_id' => 'required|integer|exists:users,id',
            'attempt_no' => 'integer|min:1',
            'user_answer_json' => 'nullable|json',
            'score' => 'integer',
            'time_taken_ms' => 'nullable|integer|min:0',
            'status' => 'in:win,fail,timeout,in_progress',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user has exceeded max attempts for this game
        $game = DB::table('door_game')->find($request->door_game_id);
        
        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Game not found',
            ], 404);
        }

        $attemptCount = DB::table('door_game_results')
            ->whereNull('deleted_at')
            ->where('door_game_id', $request->door_game_id)
            ->where('user_id', $request->user_id)
            ->count();

        if ($attemptCount >= $game->max_attempts) {
            return response()->json([
                'success' => false,
                'message' => "Maximum attempts ({$game->max_attempts}) reached for this game",
            ], 422);
        }

        $id = DB::table('door_game_results')->insertGetId([
            'uuid' => Str::uuid(),
            'door_game_id' => $request->door_game_id,
            'user_id' => $request->user_id,
            'attempt_no' => $request->input('attempt_no', $attemptCount + 1),
            'user_answer_json' => $request->user_answer_json,
            'score' => $request->input('score', 0),
            'time_taken_ms' => $request->time_taken_ms,
            'status' => $request->input('status', 'in_progress'),
            'created_at_ip' => $request->ip(),
            'updated_at_ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = DB::table('door_game_results')->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Game result created successfully',
            'data' => $result,
        ], 201);
    }

    /**
     * Display the specified game result
     */
    public function show($id)
    {
        $result = DB::table('door_game_results as dgr')
            ->join('door_game as dg', 'dgr.door_game_id', '=', 'dg.id')
            ->join('users as u', 'dgr.user_id', '=', 'u.id')
            ->whereNull('dgr.deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('dgr.id', $id)->orWhere('dgr.uuid', $id);
            })
            ->select([
                'dgr.*',
                'dg.title as game_title',
                'dg.uuid as game_uuid',
                'u.name as user_name',
                'u.email as user_email'
            ])
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Game result not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Update the specified game result
     */
    public function update(Request $request, $id)
    {
        $result = DB::table('door_game_results')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Game result not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'user_answer_json' => 'nullable|json',
            'score' => 'integer',
            'time_taken_ms' => 'nullable|integer|min:0',
            'status' => 'in:win,fail,timeout,in_progress',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = array_filter([
            'user_answer_json' => $request->input('user_answer_json'),
            'score' => $request->input('score'),
            'time_taken_ms' => $request->input('time_taken_ms'),
            'status' => $request->input('status'),
        ], function ($value) {
            return $value !== null;
        });

        if (!empty($updateData)) {
            $updateData['updated_at'] = now();
            $updateData['updated_at_ip'] = $request->ip();
            
            DB::table('door_game_results')
                ->where('id', $result->id)
                ->update($updateData);
        }

        $updatedResult = DB::table('door_game_results')->find($result->id);

        return response()->json([
            'success' => true,
            'message' => 'Game result updated successfully',
            'data' => $updatedResult,
        ]);
    }

    /**
     * Remove the specified game result (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin']);
        if ($authCheck) {
            return $authCheck;
        }

        $result = DB::table('door_game_results')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Game result not found',
            ], 404);
        }

        DB::table('door_game_results')
            ->where('id', $result->id)
            ->update(['deleted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Game result deleted successfully',
        ]);
    }

    /**
     * Get leaderboard for a specific game
     */
    public function leaderboard($gameId, Request $request)
    {
        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($gameId) {
                $q->where('id', $gameId)->orWhere('uuid', $gameId);
            })
            ->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Game not found',
            ], 404);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $query = DB::table('door_game_results as dgr')
            ->join('users as u', 'dgr.user_id', '=', 'u.id')
            ->whereNull('dgr.deleted_at')
            ->where('dgr.door_game_id', $game->id)
            ->where('dgr.status', 'win')
            ->select([
                'dgr.id',
                'dgr.uuid',
                'dgr.user_id',
                'u.name as user_name',
                'dgr.score',
                'dgr.time_taken_ms',
                'dgr.attempt_no',
                'dgr.created_at'
            ])
            ->orderBy('dgr.score', 'desc')
            ->orderBy('dgr.time_taken_ms', 'asc');

        $total = $query->count();
        $leaderboard = $query->offset($offset)->limit($perPage)->get();

        // Add rank to each entry
        $leaderboard = $leaderboard->map(function ($entry, $index) use ($offset) {
            $entry->rank = $offset + $index + 1;
            return $entry;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'game_title' => $game->title,
                'game_uuid' => $game->uuid,
                'leaderboard' => $leaderboard,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get user's results for a specific game
     */
    public function userResults($gameId, $userId, Request $request)
    {
        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($gameId) {
                $q->where('id', $gameId)->orWhere('uuid', $gameId);
            })
            ->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Game not found',
            ], 404);
        }

        $results = DB::table('door_game_results')
            ->whereNull('deleted_at')
            ->where('door_game_id', $game->id)
            ->where('user_id', $userId)
            ->orderBy('attempt_no', 'asc')
            ->get();

        $attemptsRemaining = max(0, $game->max_attempts - count($results));

        return response()->json([
            'success' => true,
            'data' => [
                'game_title' => $game->title,
                'game_uuid' => $game->uuid,
                'max_attempts' => $game->max_attempts,
                'attempts_used' => count($results),
                'attempts_remaining' => $attemptsRemaining,
                'results' => $results,
            ],
        ]);
    }

    // Helper methods from your code
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
}