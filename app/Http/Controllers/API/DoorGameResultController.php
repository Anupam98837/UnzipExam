<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class DoorGameResultController extends Controller
{

private function normalizeRole(?string $role): string
{
    return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)$role));
}
    /**
     * Display a listing of game results
     */
    public function index(Request $request)
{
    Log::info('DoorGameResult.index: start', [
        'ip' => $request->ip(),
        'query' => $request->query(),
    ]);

    try {
        // Base query
        $q = DB::table('door_game_results as dgr')
            ->join('door_game as dg', 'dgr.door_game_id', '=', 'dg.id')
            ->join('users as u', 'dgr.user_id', '=', 'u.id')
            ->whereNull('dgr.deleted_at')
            ->whereNull('dg.deleted_at')
            ->select([
                'dgr.id as result_id',
                'dgr.uuid as result_uuid',
                'dgr.door_game_id',
                'dgr.user_id',
                'dgr.attempt_no',
                'dgr.score',
                'dgr.time_taken_ms',
                'dgr.status as attempt_status',
                'dgr.created_at as result_created_at',

                'dg.id as game_id',
                'dg.uuid as game_uuid',
                'dg.title as game_title',

                'u.uuid as student_uuid',
                'u.name as student_name',
                'u.email as student_email',
            ]);

        // ✅ Filter: door_game_id (id or uuid supported)
        if ($request->filled('door_game_id')) {
            $v = (string) $request->input('door_game_id');
            $q->where(function($w) use ($v){
                if (is_numeric($v)) $w->where('dg.id', (int)$v);
                else $w->where('dg.uuid', $v)->orWhere('dg.id', $v);
            });
            Log::info('DoorGameResult.index: filter door_game_id', ['door_game_id' => $v]);
        }

        // ✅ Filter: game_uuid (your modal has this)
        if ($request->filled('game_uuid')) {
            $v = (string) $request->input('game_uuid');
            $q->where('dg.uuid', $v);
            Log::info('DoorGameResult.index: filter game_uuid', ['game_uuid' => $v]);
        }

        // ✅ Filter: student_email (your modal has this)
        if ($request->filled('student_email')) {
            $email = trim((string)$request->input('student_email'));
            $q->where('u.email', 'like', "%{$email}%");
            Log::info('DoorGameResult.index: filter student_email', ['student_email' => $email]);
        }

        // ✅ Search "q" (your toolbar uses this)
        if ($request->filled('q')) {
            $txt = trim((string)$request->input('q'));
            $q->where(function($w) use ($txt){
                $w->where('u.name', 'like', "%{$txt}%")
                  ->orWhere('u.email', 'like', "%{$txt}%")
                  ->orWhere('dg.title', 'like', "%{$txt}%");
            });
            Log::info('DoorGameResult.index: search q', ['q' => $txt]);
        }

        // ✅ Filter: attempt_status (your modal uses this)
        if ($request->filled('attempt_status')) {
            $st = (string)$request->input('attempt_status');
            $q->where('dgr.status', $st);
            Log::info('DoorGameResult.index: filter attempt_status', ['attempt_status' => $st]);
        }

        // ✅ Date filters (your modal uses from/to)
        if ($request->filled('from')) {
            $q->whereDate('dgr.created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('dgr.created_at', '<=', $request->input('to'));
        }

        // ✅ % filters (optional; your UI has min/max %)
        // Door game doesn't have "total points" in DB; so we treat score as 0..1
        // If your door game score is 0/1, accuracy = score*100.
        if ($request->filled('min_percentage')) {
            $minPct = (float)$request->input('min_percentage');
            $q->whereRaw('(dgr.score * 100) >= ?', [$minPct]);
        }
        if ($request->filled('max_percentage')) {
            $maxPct = (float)$request->input('max_percentage');
            $q->whereRaw('(dgr.score * 100) <= ?', [$maxPct]);
        }

        // ✅ Tab filter (published/unpublished) — keep compatible even if you don't store it
        // If you don't have publish_to_student column, we just ignore filter safely.
        // (Front-end shows Published/Not published using result.publish_to_student)
        $hasPublish = false;
        try { $hasPublish = \Illuminate\Support\Facades\Schema::hasColumn('door_game_results', 'publish_to_student'); }
        catch (\Throwable $e) { $hasPublish = false; }

        if ($hasPublish && $request->filled('publish_to_student')) {
            $q->where('dgr.publish_to_student', (int)$request->input('publish_to_student'));
        }

        // ✅ Sorting (your UI sends sort = -result_created_at etc.)
        $sort = (string) $request->input('sort', '-result_created_at');
        $sortMap = [
            'student_name'       => 'u.name',
            'game_title'         => 'dg.title',
            'score'              => 'dgr.score',
            'accuracy'           => DB::raw('(dgr.score * 100)'),
            'result_created_at'  => 'dgr.created_at',
        ];

        $dir = 'asc';
        $col = $sort;
        if (str_starts_with($sort, '-')) {
            $dir = 'desc';
            $col = substr($sort, 1);
        }

        if (isset($sortMap[$col])) {
            $q->orderBy($sortMap[$col], $dir);
        } else {
            $q->orderBy('dgr.created_at', 'desc');
        }

        // ✅ Pagination
        $perPage = max(1, min(100, (int)$request->input('per_page', 20)));
        $page    = max(1, (int)$request->input('page', 1));

        $total = (clone $q)->count();
        $rows  = $q->forPage($page, $perPage)->get();

        // ✅ Normalize to Bubble-style shape (THIS fixes your UI)
        $items = $rows->map(function($r) use ($hasPublish) {
            $accuracy = ($r->score !== null) ? round(((float)$r->score) * 100, 2) : null;

            return [
                'student' => [
                    'id'    => (int)$r->user_id,
                    'uuid'  => (string)($r->student_uuid ?? ''),
                    'name'  => (string)($r->student_name ?? ''),
                    'email' => (string)($r->student_email ?? ''),
                ],
                'game' => [
                    'id'    => (int)$r->game_id,
                    'uuid'  => (string)($r->game_uuid ?? ''),
                    'title' => (string)($r->game_title ?? ''),
                ],
                'attempt' => [
                    'status' => (string)($r->attempt_status ?? ''),
                ],
                'result' => [
                    'id'                => (int)$r->result_id,
                    'uuid'              => (string)($r->result_uuid ?? ''),
                    'attempt_no'        => (int)($r->attempt_no ?? 0),
                    'score'             => (int)($r->score ?? 0),
                    'accuracy'          => $accuracy,
                    'publish_to_student'=> $hasPublish ? (int)($r->publish_to_student ?? 0) : 0,
                    'result_created_at' => $r->result_created_at
                        ? \Carbon\Carbon::parse($r->result_created_at)->toDateTimeString()
                        : null,
                ],
            ];
        })->values();

        $lastPage = (int) ceil($total / max($perPage, 1));

        Log::info('DoorGameResult.index: success', [
            'total' => $total,
            'returned' => $items->count(),
            'last_page' => $lastPage,
        ]);

        // ✅ IMPORTANT: return exactly what your JS can read:
        // json.data (array) + json.pagination (object)
        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total'       => (int)$total,
                'per_page'    => (int)$perPage,
                'page'        => (int)$page,
                'total_pages' => (int)$lastPage,
            ],
        ]);

    } catch (\Throwable $e) {
        Log::error('DoorGameResult.index: exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Server error while fetching results',
        ], 500);
    }
}

    /**
     * Store a newly created game result
     */
    public function store(Request $request)
    {
        Log::info('DoorGameResult.store: start', [
            'ip' => $request->ip(),
            'payload_keys' => array_keys($request->all()),
        ]);

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
            Log::warning('DoorGameResult.store: validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if user has exceeded max attempts for this game
            $game = DB::table('door_game')->find($request->door_game_id);

            if (!$game) {
                Log::warning('DoorGameResult.store: game not found', [
                    'door_game_id' => $request->door_game_id,
                ]);

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

            Log::info('DoorGameResult.store: attempts meta', [
                'door_game_id' => $request->door_game_id,
                'user_id' => $request->user_id,
                'attempts_used' => $attemptCount,
                'max_attempts' => $game->max_attempts ?? null,
                'requested_attempt_no' => $request->input('attempt_no'),
            ]);

            if ($attemptCount >= $game->max_attempts) {
                Log::warning('DoorGameResult.store: max attempts reached', [
                    'door_game_id' => $request->door_game_id,
                    'user_id' => $request->user_id,
                    'attempts_used' => $attemptCount,
                    'max_attempts' => $game->max_attempts,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Maximum attempts ({$game->max_attempts}) reached for this game",
                ], 422);
            }

            DB::beginTransaction();

            $uuid = (string) Str::uuid();

            $id = DB::table('door_game_results')->insertGetId([
                'uuid' => $uuid,
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

            DB::commit();

            Log::info('DoorGameResult.store: created', [
                'door_game_result_id' => $id,
                'uuid' => $uuid,
                'door_game_id' => $request->door_game_id,
                'user_id' => $request->user_id,
                'attempt_no' => $result->attempt_no ?? null,
                'status' => $result->status ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Game result created successfully',
                'data' => $result,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('DoorGameResult.store: exception', [
                'door_game_id' => $request->door_game_id ?? null,
                'user_id' => $request->user_id ?? null,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while creating result',
            ], 500);
        }
    }

    /**
     * Display the specified game result
     */
    public function show($id)
    {
        Log::info('DoorGameResult.show: start', ['id_or_uuid' => $id]);

        try {
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
                Log::warning('DoorGameResult.show: not found', ['id_or_uuid' => $id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Game result not found',
                ], 404);
            }

            Log::info('DoorGameResult.show: success', [
                'door_game_result_id' => $result->id ?? null,
                'uuid' => $result->uuid ?? null,
                'door_game_id' => $result->door_game_id ?? null,
                'user_id' => $result->user_id ?? null,
                'status' => $result->status ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGameResult.show: exception', [
                'id_or_uuid' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching result',
            ], 500);
        }
    }

    /**
     * Update the specified game result
     */
    public function update(Request $request, $id)
    {
        Log::info('DoorGameResult.update: start', [
            'id_or_uuid' => $id,
            'ip' => $request->ip(),
            'payload_keys' => array_keys($request->all()),
        ]);

        $result = DB::table('door_game_results')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$result) {
            Log::warning('DoorGameResult.update: not found', ['id_or_uuid' => $id]);

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
            Log::warning('DoorGameResult.update: validation failed', [
                'id_or_uuid' => $id,
                'door_game_result_id' => $result->id,
                'errors' => $validator->errors()->toArray(),
            ]);

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

        if (empty($updateData)) {
            Log::info('DoorGameResult.update: no changes provided', [
                'id_or_uuid' => $id,
                'door_game_result_id' => $result->id,
            ]);

            $updatedResult = DB::table('door_game_results')->find($result->id);

            return response()->json([
                'success' => true,
                'message' => 'Game result updated successfully',
                'data' => $updatedResult,
            ]);
        }

        try {
            DB::beginTransaction();

            $updateData['updated_at'] = now();
            $updateData['updated_at_ip'] = $request->ip();

            Log::info('DoorGameResult.update: updating', [
                'door_game_result_id' => $result->id,
                'uuid' => $result->uuid,
                'fields' => array_keys($updateData),
            ]);

            DB::table('door_game_results')
                ->where('id', $result->id)
                ->update($updateData);

            $updatedResult = DB::table('door_game_results')->find($result->id);

            DB::commit();

            Log::info('DoorGameResult.update: success', [
                'door_game_result_id' => $result->id,
                'uuid' => $result->uuid,
                'status' => $updatedResult->status ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Game result updated successfully',
                'data' => $updatedResult,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('DoorGameResult.update: exception', [
                'door_game_result_id' => $result->id ?? null,
                'uuid' => $result->uuid ?? null,
                'id_or_uuid' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while updating result',
            ], 500);
        }
    }

    /**
     * Remove the specified game result (soft delete)
     */
    public function destroy(Request $request, $id)
    {
        Log::info('DoorGameResult.destroy: start', [
            'id_or_uuid' => $id,
            'ip' => $request->ip(),
        ]);

        // Check role authorization
        $authCheck = $this->requireRole($request, ['admin']);
        if ($authCheck) {
            Log::warning('DoorGameResult.destroy: unauthorized', [
                'actor' => $this->actor($request),
                'allowed' => ['admin'],
                'id_or_uuid' => $id,
            ]);
            return $authCheck;
        }

        $result = DB::table('door_game_results')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            })
            ->first();

        if (!$result) {
            Log::warning('DoorGameResult.destroy: not found', ['id_or_uuid' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Game result not found',
            ], 404);
        }

        try {
            DB::table('door_game_results')
                ->where('id', $result->id)
                ->update(['deleted_at' => now(), 'updated_at' => now(), 'updated_at_ip' => $request->ip()]);

            Log::info('DoorGameResult.destroy: soft deleted', [
                'door_game_result_id' => $result->id,
                'uuid' => $result->uuid,
                'actor' => $this->actor($request),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Game result deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGameResult.destroy: exception', [
                'door_game_result_id' => $result->id ?? null,
                'uuid' => $result->uuid ?? null,
                'id_or_uuid' => $id,
                'actor' => $this->actor($request),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while deleting result',
            ], 500);
        }
    }

    /**
     * Get leaderboard for a specific game
     */
    public function leaderboard($gameId, Request $request)
    {
        Log::info('DoorGameResult.leaderboard: start', [
            'game_id_or_uuid' => $gameId,
            'ip' => $request->ip(),
            'query' => $request->query(),
        ]);

        try {
            $game = DB::table('door_game')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($gameId) {
                    $q->where('id', $gameId)->orWhere('uuid', $gameId);
                })
                ->first();

            if (!$game) {
                Log::warning('DoorGameResult.leaderboard: game not found', [
                    'game_id_or_uuid' => $gameId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Game not found',
                ], 404);
            }

            $perPage = (int) $request->input('per_page', 10);
            $page = (int) $request->input('page', 1);
            $offset = ($page - 1) * $perPage;

            Log::info('DoorGameResult.leaderboard: pagination', [
                'per_page' => $perPage,
                'page' => $page,
                'offset' => $offset,
                'door_game_id' => $game->id,
            ]);

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

            Log::info('DoorGameResult.leaderboard: fetched', [
                'door_game_id' => $game->id,
                'total' => $total,
                'returned' => $leaderboard->count(),
            ]);

            // Add rank to each entry
            $leaderboard = $leaderboard->map(function ($entry, $index) use ($offset) {
                $entry->rank = $offset + $index + 1;
                return $entry;
            });

            Log::info('DoorGameResult.leaderboard: success', [
                'door_game_id' => $game->id,
                'game_uuid' => $game->uuid,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($total / max($perPage, 1)),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'game_title' => $game->title,
                    'game_uuid' => $game->uuid,
                    'leaderboard' => $leaderboard,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / max($perPage, 1)),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGameResult.leaderboard: exception', [
                'game_id_or_uuid' => $gameId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching leaderboard',
            ], 500);
        }
    }

    /**
     * Get user's results for a specific game
     */
    public function userResults($gameId, $userId, Request $request)
    {
        Log::info('DoorGameResult.userResults: start', [
            'game_id_or_uuid' => $gameId,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'query' => $request->query(),
        ]);

        try {
            $game = DB::table('door_game')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($gameId) {
                    $q->where('id', $gameId)->orWhere('uuid', $gameId);
                })
                ->first();

            if (!$game) {
                Log::warning('DoorGameResult.userResults: game not found', [
                    'game_id_or_uuid' => $gameId,
                    'user_id' => $userId,
                ]);

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

            $attemptsRemaining = max(0, (int) $game->max_attempts - $results->count());

            Log::info('DoorGameResult.userResults: success', [
                'door_game_id' => $game->id,
                'game_uuid' => $game->uuid,
                'user_id' => $userId,
                'attempts_used' => $results->count(),
                'max_attempts' => (int) $game->max_attempts,
                'attempts_remaining' => $attemptsRemaining,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'game_title' => $game->title,
                    'game_uuid' => $game->uuid,
                    'max_attempts' => $game->max_attempts,
                    'attempts_used' => $results->count(),
                    'attempts_remaining' => $attemptsRemaining,
                    'results' => $results,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DoorGameResult.userResults: exception', [
                'game_id_or_uuid' => $gameId,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching user results',
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

        Log::debug('DoorGameResult.actor', $actor);

        return $actor;
    }

    private function requireRole(Request $request, array $allowed)
    {
        $actor = $this->actor($request);

        if (!$actor['role'] || !in_array($actor['role'], $allowed, true)) {
            Log::warning('DoorGameResult.requireRole: forbidden', [
                'actor' => $actor,
                'allowed' => $allowed,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error'   => 'Unauthorized Access',
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        Log::info('DoorGameResult.requireRole: allowed', [
            'actor' => $actor,
            'allowed' => $allowed,
        ]);

        return null;
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

   public function submit(Request $request, string $game_uuid)
{
    Log::info('DoorGame.submit: start', [
        'ip' => $request->ip(),
        'game_uuid' => $game_uuid,
        'payload_keys' => array_keys($request->all()),
    ]);

    // ✅ token-safe actor (CheckRole middleware fills these)
    $actor  = $this->actor($request);
    $userId = (int) ($actor['id'] ?? 0);

    if ($userId <= 0) {
        Log::warning('DoorGame.submit: actor id missing', ['actor' => $actor]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to resolve user from token (actor id missing).'
        ], 403);
    }

    // ✅ Base validation: accept your CURRENT payload format
    $validator = Validator::make($request->all(), [
        // frontend may send any of these; we keep them optional
        'game_uuid' => ['nullable','uuid'],
        'door_game_uuid' => ['nullable','uuid'],

        // frontend currently sends these, but server won't trust them
        'status' => ['nullable', Rule::in(['win','fail','timeout','in_progress'])],
        'score' => ['nullable','integer'],
        'time_taken_ms' => ['nullable','integer','min:0'],

        // ✅ this is what we really need
        'user_answer_json' => ['required'],
    ]);

    if ($validator->fails()) {
        Log::warning('DoorGame.submit: base validation failed', [
            'errors' => $validator->errors()->toArray(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    // ✅ If body contains uuid, ensure it matches URL (avoid mismatch bugs)
    $bodyUuid = $request->input('door_game_uuid') ?: $request->input('game_uuid');
    if ($bodyUuid && $bodyUuid !== $game_uuid) {
        Log::warning('DoorGame.submit: uuid mismatch (url vs body)', [
            'url' => $game_uuid,
            'body' => $bodyUuid,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'game_uuid mismatch (URL vs body)'
        ], 422);
    }

    // ✅ Load game from your door_game table
    $game = DB::table('door_game')
        ->where('uuid', $game_uuid)
        ->whereNull('deleted_at')
        ->first();

    if (!$game) {
        Log::warning('DoorGame.submit: game not found', ['game_uuid' => $game_uuid]);

        return response()->json([
            'success' => false,
            'message' => 'Game not found'
        ], 404);
    }

    // ✅ Decode user_answer_json robustly (supports: array, JSON string, double-encoded JSON)
    $ua = $request->input('user_answer_json');
    $decoded = null;

    if (is_array($ua)) {
        $decoded = $ua;
    } elseif (is_string($ua)) {
        $decoded = json_decode($ua, true);

        // if double-encoded, first decode returns string -> decode again
        if (is_string($decoded)) {
            $decoded2 = json_decode($decoded, true);
            if (is_array($decoded2)) $decoded = $decoded2;
        }
    }

    // unwrap if accidentally sent { data: {...} }
    if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
        $decoded = $decoded['data'];
    }

    Log::info('DoorGame.submit: decoded snapshot', [
        'decoded_type' => gettype($decoded),
        'decoded_keys' => is_array($decoded) ? array_keys($decoded) : null,
    ]);

    if (!is_array($decoded)) {
        Log::warning('DoorGame.submit: invalid user_answer_json', [
            'incoming_type' => gettype($ua),
            'incoming_sample' => is_string($ua) ? substr($ua, 0, 180) : null,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'user_answer_json must be valid JSON object',
        ], 422);
    }

    // ✅ Patch timing if your frontend sends time_taken_ms and doesn't send timing object
    // Your frontend sends: { user_answer_json: { time_taken_ms, started_at_ms, ended_at_ms, status, moves, ... } }
    // So we convert it to expected structure: timing.time_taken_ms + started_at/finished_at
    if (!isset($decoded['timing']) || !is_array($decoded['timing'])) {
        $decoded['timing'] = [];
    }

    // If frontend used top-level time_taken_ms in decoded json
    if (!isset($decoded['timing']['time_taken_ms']) && isset($decoded['time_taken_ms'])) {
        $decoded['timing']['time_taken_ms'] = (int) $decoded['time_taken_ms'];
    }

    // If frontend sent request->time_taken_ms (top-level request)
    if (!isset($decoded['timing']['time_taken_ms']) && $request->filled('time_taken_ms')) {
        $decoded['timing']['time_taken_ms'] = (int) $request->input('time_taken_ms');
    }

    // Convert started_at_ms / ended_at_ms to started_at / finished_at (optional)
    if (!isset($decoded['timing']['started_at']) && isset($decoded['started_at_ms'])) {
        $decoded['timing']['started_at'] = now()->toDateTimeString(); // fallback
    }
    if (!isset($decoded['timing']['finished_at']) && isset($decoded['ended_at_ms'])) {
        $decoded['timing']['finished_at'] = now()->toDateTimeString(); // fallback
    }

    // ✅ Your frontend currently does NOT send start_index/path/events,
    // so we PATCH them from moves (works for your current JS payload)
    // start_index = first move.from (or user_start_cell or current userId)
    if (!isset($decoded['start_index'])) {
        $decoded['start_index'] = (int) (
            data_get($decoded, 'moves.0.from')
            ?? data_get($decoded, 'user_start_cell')
            ?? data_get($decoded, 'user_end_cell')
            ?? 1
        );
    }

    // path = sequence derived from moves [from, to, to, ...]
    if (!isset($decoded['path']) || !is_array($decoded['path']) || count($decoded['path']) < 1) {
        $path = [];
        $moves = is_array($decoded['moves'] ?? null) ? $decoded['moves'] : [];

        if (!empty($moves)) {
            $firstFrom = (int) ($moves[0]['from'] ?? 0);
            if ($firstFrom > 0) $path[] = $firstFrom;

            foreach ($moves as $m) {
                $to = (int) ($m['to'] ?? 0);
                if ($to > 0) $path[] = $to;
            }
        } else {
            $fallback = (int) ($decoded['start_index'] ?? 1);
            $path = [$fallback];
        }

        // remove duplicates like [1,1] if any
        $clean = [];
        foreach ($path as $p) {
            if (empty($clean) || end($clean) !== $p) $clean[] = $p;
        }
        $decoded['path'] = $clean;
    }

    // events: infer door/key events from keys_collected + door_cell
    if (!isset($decoded['events']) || !is_array($decoded['events'])) {
        $decoded['events'] = [];
    }

    // key event (if any key collected)
    $keysCollected = $decoded['keys_collected'] ?? null;
    if (is_array($keysCollected) && count($keysCollected) > 0) {
        $decoded['events']['key'] = $decoded['events']['key'] ?? [];
        if (!isset($decoded['events']['key']['picked_at_index'])) {
            // last collected key cell id
            $decoded['events']['key']['picked_at_index'] = (int) end($keysCollected);
        }
        if (!isset($decoded['events']['key']['t_ms'])) {
            $decoded['events']['key']['t_ms'] = (int) data_get($decoded, 'moves.'.(count($decoded['moves'] ?? [])-1).'.t_ms', 0);
        }
    }

    // door event (if ended on door cell)
    $doorCell = (int) ($decoded['door_cell'] ?? 0);
    $endCell  = (int) ($decoded['user_end_cell'] ?? 0);
    if ($doorCell > 0 && $endCell === $doorCell) {
        $decoded['events']['door'] = $decoded['events']['door'] ?? [];
        if (!isset($decoded['events']['door']['opened_at_index'])) {
            $decoded['events']['door']['opened_at_index'] = $doorCell;
        }
        if (!isset($decoded['events']['door']['t_ms'])) {
            $decoded['events']['door']['t_ms'] = (int) data_get($decoded, 'moves.'.(count($decoded['moves'] ?? [])-1).'.t_ms', 0);
        }
    }

    // ✅ Inner validation (NOW matches your JS payload after patching)
    $innerValidator = Validator::make($decoded, [
        'grid_dim' => ['required','integer','min:1','max:10'],
        'start_index' => ['required','integer','min:1'],
        'path' => ['required','array','min:1'],
        'path.*' => ['integer','min:1'],

        'moves' => ['nullable','array'],
        'moves.*.from' => ['required_with:moves','integer','min:1'],
        'moves.*.to'   => ['required_with:moves','integer','min:1'],
        'moves.*.t_ms' => ['required_with:moves','integer','min:0'],

        'events' => ['nullable','array'],
        'events.key.picked_at_index'  => ['nullable','integer','min:1'],
        'events.key.t_ms'             => ['nullable','integer','min:0'],
        'events.door.opened_at_index' => ['nullable','integer','min:1'],
        'events.door.t_ms'            => ['nullable','integer','min:0'],

        'timing' => ['required','array'],
        'timing.time_taken_ms' => ['required','integer','min:0'],
    ]);

    if ($innerValidator->fails()) {
        Log::warning('DoorGame.submit: inner validation failed', [
            'game_uuid' => $game_uuid,
            'errors' => $innerValidator->errors()->toArray(),
            'decoded_keys' => array_keys($decoded),
            'timing_keys' => (isset($decoded['timing']) && is_array($decoded['timing'])) ? array_keys($decoded['timing']) : null,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $innerValidator->errors(),
        ], 422);
    }

    // ✅ Ensure grid_dim matches your door_game.grid_dim
    if ((int) $decoded['grid_dim'] !== (int) ($game->grid_dim ?? 0)) {
        Log::warning('DoorGame.submit: grid_dim mismatch', [
            'game_uuid' => $game_uuid,
            'game_grid_dim' => (int) ($game->grid_dim ?? 0),
            'payload_grid_dim' => (int) $decoded['grid_dim'],
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid grid_dim for this game',
        ], 422);
    }

    // ✅ Determine status from payload + time limit (don’t trust client)
    $timeTaken = (int) data_get($decoded, 'timing.time_taken_ms', 0);

    // If JS already marked timeout, keep it
    $clientStatus = (string) ($decoded['status'] ?? $request->input('status') ?? 'in_progress');

    // Win rule (for your current JS):
    // - win only if all keys collected AND end cell == door cell
    $keysTotal = (int) ($decoded['keys_total'] ?? 0);
    $keysGot   = is_array($decoded['keys_collected'] ?? null) ? count($decoded['keys_collected']) : 0;
    $doorCell  = (int) ($decoded['door_cell'] ?? 0);
    $endCell   = (int) ($decoded['user_end_cell'] ?? 0);

    $status = ($keysTotal > 0 && $keysGot >= $keysTotal && $doorCell > 0 && $endCell === $doorCell) ? 'win' : 'fail';

    // apply time limit override
    $limitMs = (int) ($game->time_limit_sec ?? 0) * 1000;
    if ($clientStatus === 'timeout') {
        $status = 'timeout';
    } elseif ($limitMs > 0 && $timeTaken > $limitMs) {
        $status = 'timeout';
    }

    // score (simple)
    $score = ($status === 'win') ? 1 : 0;

    try {
        return DB::transaction(function () use ($request, $userId, $game, $decoded, $game_uuid, $status, $score, $timeTaken) {

            // ✅ STRICT RULE: attempt_no must always be <= door_game.max_attempts
            $maxAttempts = (int) ($game->max_attempts ?? 1);
            if ($maxAttempts <= 0) $maxAttempts = 1;

            // lock rows for this user+game and count attempts
            $attemptsUsed = (int) DB::table('door_game_results')
                ->where('door_game_id', (int) $game->id)
                ->where('user_id', (int) $userId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->count();

            $nextAttempt = $attemptsUsed + 1;

            if ($nextAttempt > $maxAttempts) {
                Log::warning('DoorGame.submit: max attempts reached', [
                    'game_uuid' => $game_uuid,
                    'door_game_id' => (int) $game->id,
                    'user_id' => (int) $userId,
                    'max_attempts' => $maxAttempts,
                    'attempts_used' => $attemptsUsed,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Maximum attempts reached for this game',
                    'max_attempts' => $maxAttempts,
                    'attempts_used' => $attemptsUsed,
                ], 403);
            }

            $resultUuid = (string) Str::uuid();

            $resultId = DB::table('door_game_results')->insertGetId([
                'uuid'             => $resultUuid,
                'door_game_id'     => (int) $game->id,
                'user_id'          => (int) $userId,
                'attempt_no'       => (int) $nextAttempt,
                'user_answer_json' => json_encode($decoded, JSON_UNESCAPED_UNICODE),

                'score'            => (int) $score,
                'time_taken_ms'    => (int) $timeTaken,
                'status'           => (string) $status,

                'created_at'       => now(),
                'updated_at'       => now(),
                'created_at_ip'    => $request->ip(),
                'updated_at_ip'    => $request->ip(),
            ]);

            Log::info('DoorGame.submit: created', [
                'door_game_result_id' => (int) $resultId,
                'uuid' => $resultUuid,
                'door_game_id' => (int) $game->id,
                'user_id' => (int) $userId,
                'attempt_no' => (int) $nextAttempt,
                'status' => (string) $status,
                'time_taken_ms' => (int) $timeTaken,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Submitted successfully',
                'data' => [
                    'id'            => (int) $resultId,
                    'uuid'          => (string) $resultUuid,
                    'attempt_no'    => (int) $nextAttempt,
                    'score'         => (int) $score,
                    'status'        => (string) $status,
                    'time_taken_ms' => (int) $timeTaken,
                    'max_attempts'  => (int) $maxAttempts,
                ]
            ], 201);
        });

    } catch (\Throwable $e) {
        Log::error('DoorGame.submit: exception', [
            'game_uuid' => $game_uuid,
            'door_game_id' => $game->id ?? null,
            'user_id' => $userId,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Submit failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}

public function resultDetail(Request $request, string $resultKey)
    {
        // if ($resp = $this->requireRole($request, ['student','admin','super_admin'])) return $resp;

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
/**
 * Resolve a door game by "key" which can be UUID or numeric ID.
 * Returns a row from door_game table or null.
 */
private function gameByKeyFixed(string $key)
{
    $key = trim((string)$key);
    if ($key === '') return null;

    // UUID?
    $isUuid = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key);

    $q = DB::table('door_game')->whereNull('deleted_at');

    if ($isUuid) {
        return $q->where('uuid', $key)->first();
    }

    // numeric id?
    if (ctype_digit($key)) {
        return $q->where('id', (int)$key)->first();
    }

    // fallback: try uuid anyway
    return $q->where('uuid', $key)->first();
}

/**
 * Returns door_game_id from a given key, or null if not found.
 */
private function gameIdByKeyFixed(string $key): ?int
{
    $g = $this->gameByKeyFixed($key);
    return $g ? (int)$g->id : null;
}

}
