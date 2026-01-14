<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DoorGameController extends Controller
{
    /**
     * Display a listing of door games
     */
    public function index(Request $request)
    {
        $query = DB::table('door_game')->whereNull('deleted_at');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by title if provided
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $total = $query->count();
        $games = $query->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $games,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Store a newly created door game
     */
    public function store(Request $request)
    {
        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin', 'creator']);
        if ($authCheck) {
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate grid_json structure
        $gridData = json_decode($request->grid_json, true);
        $expectedCells = $request->grid_dim * $request->grid_dim;
        
        if (!is_array($gridData) || count($gridData) !== $expectedCells) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'grid_json' => ["Grid must contain exactly {$expectedCells} cells for a {$request->grid_dim}x{$request->grid_dim} grid"]
                ],
            ], 422);
        }

        $actor = $this->actor($request);

        $id = DB::table('door_game')->insertGetId([
            'uuid' => Str::uuid(),
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

        return response()->json([
            'success' => true,
            'message' => 'Door game created successfully',
            'data' => $game,
        ], 201);
    }

    /**
     * Display the specified door game
     */
    public function show($id)
    {
        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Door game not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $game,
        ]);
    }

    /**
     * Update the specified door game
     */
    public function update(Request $request, $id)
    {
        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin', 'creator']);
        if ($authCheck) {
            return $authCheck;
        }

        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$game) {
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate grid_json if both grid_json and grid_dim are provided
        if ($request->has('grid_json')) {
            $gridDim = $request->input('grid_dim', $game->grid_dim);
            $gridData = json_decode($request->grid_json, true);
            $expectedCells = $gridDim * $gridDim;
            
            if (!is_array($gridData) || count($gridData) !== $expectedCells) {
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

        if (!empty($updateData)) {
            $updateData['updated_at'] = now();
            
            DB::table('door_game')
                ->where('id', $game->id)
                ->update($updateData);
        }

        $updatedGame = DB::table('door_game')->find($game->id);

        return response()->json([
            'success' => true,
            'message' => 'Door game updated successfully',
            'data' => $updatedGame,
        ]);
    }

    /**
     * Remove the specified door game (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin']);
        if ($authCheck) {
            return $authCheck;
        }

        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Door game not found',
            ], 404);
        }

        DB::table('door_game')
            ->where('id', $game->id)
            ->update(['deleted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Door game deleted successfully',
        ]);
    }

    /**
     * Get active games list (public endpoint)
     */
    public function activeGames(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
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

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $games,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get game details for playing (public endpoint)
     * Returns game without solution data
     */
    public function playGame($id)
    {
        $game = DB::table('door_game')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Game not found or not active',
            ], 404);
        }

        // Parse grid and hide solution-related data for playing
        $gridData = json_decode($game->grid_json, true);
        
        // Remove solution keys from each cell for initial play
        $playGrid = array_map(function ($cell) {
            return [
                'id' => $cell['id'] ?? null,
                'label' => $cell['label'] ?? null,
                'type' => $cell['type'] ?? 'door',
                // Don't expose 'is_correct' or similar fields
            ];
        }, $gridData);

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