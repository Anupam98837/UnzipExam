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
            )
            ->whereNull('bubble_game.deleted_at');

        // Filter by status
        if ($request->has('status')) {
            $query->where('bubble_game.status', $request->status);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('bubble_game.title', 'like', '%' . $request->search . '%');
        }

        // Order by
        $orderBy = $request->get('order_by', 'bubble_game.created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $bubbleGames = $query->offset($offset)->limit($perPage)->get();

        // Decode JSON fields
        foreach ($bubbleGames as $game) {
            $game->metadata = json_decode($game->metadata);
        }

        return response()->json([
            'success' => true,
            'data' => $bubbleGames,
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
}