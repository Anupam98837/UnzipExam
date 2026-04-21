<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
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
        // ✅ token-safe actor
        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        /*
        |----------------------------------------------------------------------
        | ✅ Helpers (multi filters + safe cleaning)
        |----------------------------------------------------------------------
        */
        $clean = function ($v) {
            if ($v === null) return null;

            if (is_string($v)) {
                $v = trim($v);
                if ($v === '') return null;

                $low = strtolower($v);
                if (in_array($low, ['all', 'any', 'null', 'undefined', 'none'], true)) return null;
                return $v;
            }

            return $v;
        };

        $toStrList = function ($v) use ($clean) {
            if ($v === null) return [];

            $arr = is_array($v)
                ? $v
                : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);

            $out = [];
            foreach ($arr as $item) {
                $item = $clean($item);
                if ($item !== null) $out[] = (string)$item;
            }

            return array_values(array_unique($out));
        };

        $toIntList = function ($v) use ($clean) {
            if ($v === null) return [];

            $arr = is_array($v)
                ? $v
                : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);

            $out = [];
            foreach ($arr as $item) {
                $item = $clean($item);
                if ($item !== null && is_numeric($item)) {
                    $n = (int)$item;
                    if ($n > 0) $out[] = $n;
                }
            }

            return array_values(array_unique($out));
        };

        $toBool01 = function ($v) use ($clean) {
            $v = $clean($v);
            if ($v === null) return null;

            if (in_array((string)$v, ['0', '1'], true)) return (int)$v;

            $b = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($b === null) return null;

            return $b ? 1 : 0;
        };

        $toFloat = function ($v) use ($clean) {
            $v = $clean($v);
            if ($v === null) return null;
            if (!is_numeric($v)) return null;
            return (float)$v;
        };

        /*
        |----------------------------------------------------------------------
        | ✅ publish column safe
        |----------------------------------------------------------------------
        */
        $hasPublish = false;
        try {
            $hasPublish = Schema::hasColumn('door_game_results', 'publish_to_student');
        } catch (\Throwable $e) {
            $hasPublish = false;
        }

        /*
        |----------------------------------------------------------------------
        | ✅ folder table safe
        |----------------------------------------------------------------------
        */
        $hasFolderTable = false;
        $hasFolderDeletedAt = false;

        try {
            $hasFolderTable = Schema::hasTable('user_folders');
            if ($hasFolderTable) {
                $hasFolderDeletedAt = Schema::hasColumn('user_folders', 'deleted_at');
            }
        } catch (\Throwable $e) {
            $hasFolderTable = false;
            $hasFolderDeletedAt = false;
        }

        /*
        |----------------------------------------------------------------------
        | ✅ users soft delete safe
        |----------------------------------------------------------------------
        */
        $hasUserDeletedAt = false;
        try {
            $hasUserDeletedAt = Schema::hasColumn('users', 'deleted_at');
        } catch (\Throwable $e) {
            $hasUserDeletedAt = false;
        }

        /*
        |----------------------------------------------------------------------
        | ✅ Base query
        |----------------------------------------------------------------------
        */
        $q = DB::table('door_game_results as dgr')
            ->join('door_game as dg', 'dgr.door_game_id', '=', 'dg.id')
            ->join('users as u', 'dgr.user_id', '=', 'u.id')
            ->whereNull('dgr.deleted_at')
            ->whereNull('dg.deleted_at');

        if ($hasUserDeletedAt) {
            $q->whereNull('u.deleted_at');
        }

        // ✅ JOIN folder if exists
        if ($hasFolderTable) {
            $q->leftJoin('user_folders as uf', function ($j) use ($hasFolderDeletedAt) {
                $j->on('uf.id', '=', 'u.user_folder_id');
                if ($hasFolderDeletedAt) {
                    $j->whereNull('uf.deleted_at');
                }
            });
        }

        /*
        |----------------------------------------------------------------------
        | ✅ Accuracy % expression (DoorGame score is 0/1 -> accuracy = score*100)
        |----------------------------------------------------------------------
        */
        $accExpr = '(COALESCE(dgr.score,0) * 100.0)';

        /*
        |----------------------------------------------------------------------
        | ✅ SELECT columns
        |----------------------------------------------------------------------
        */
        $select = [
            // result
            'dgr.id as result_id',
            'dgr.uuid as result_uuid',
            'dgr.door_game_id',
            'dgr.user_id',
            'dgr.attempt_no',
            'dgr.score',
            'dgr.time_taken_ms',
            'dgr.status as attempt_status',
            'dgr.created_at as result_created_at',

            // game
            'dg.id as game_id',
            'dg.uuid as game_uuid',
            'dg.title as game_title',
            'dg.status as game_status',

            // student
            'u.id as student_id',
            'u.uuid as student_uuid',
            'u.name as student_name',
            'u.email as student_email',
            'u.user_folder_id as user_folder_id',

            // ✅ computed accuracy in SQL (so min/max works)
            DB::raw("ROUND($accExpr, 2) as accuracy_pct"),
        ];

        // publish column safe
        if ($hasPublish) {
            $select[] = 'dgr.publish_to_student';
        } else {
            $select[] = DB::raw('0 as publish_to_student');
        }

        // folder title safe
        if ($hasFolderTable) {
            $select[] = 'uf.id as folder_id';
            $select[] = 'uf.title as folder_title';
        } else {
            $select[] = DB::raw('NULL as folder_id');
            $select[] = DB::raw('NULL as folder_title');
        }

        $q->select($select);

        /*
        |----------------------------------------------------------
        | ✅ Student visibility rule:
        | student sees ONLY own results + only published
        |----------------------------------------------------------
        */
        if ($role === 'student') {
            $q->where('dgr.user_id', $userId);
            if ($hasPublish) {
                $q->where('dgr.publish_to_student', 1);
            }
        }

        /*
        |----------------------------------------------------------
        | ✅ Filters (MULTI + COMBINABLE)
        |----------------------------------------------------------
        */

        // door_game_id supports mixed list (ids + uuids) => ?door_game_id=1,2,uuid1
        $doorGameKeys = $toStrList($request->query('door_game_id'));
        if (!empty($doorGameKeys)) {
            $ids = [];
            $uuids = [];

            foreach ($doorGameKeys as $v) {
                if (is_numeric($v)) $ids[] = (int)$v;
                else $uuids[] = (string)$v;
            }

            $ids = array_values(array_unique($ids));
            $uuids = array_values(array_unique($uuids));

            $q->where(function ($w) use ($ids, $uuids) {
                $hasAny = false;
                if (!empty($ids)) {
                    $w->orWhereIn('dg.id', $ids);
                    $hasAny = true;
                }
                if (!empty($uuids)) {
                    $w->orWhereIn('dg.uuid', $uuids);
                    $hasAny = true;
                }
                // safety no-op if empty (but it will never be empty here)
                if (!$hasAny) {
                    $w->whereRaw('1=1');
                }
            });
        }

        // game_uuid multi
        $gameUuids = $toStrList($request->query('game_uuid'));
        if (!empty($gameUuids)) {
            $q->whereIn('dg.uuid', $gameUuids);
        }

        // student_email multi terms (OR)
        $emailTerms = $toStrList($request->query('student_email'));
        if (!empty($emailTerms)) {
            $q->where(function ($w) use ($emailTerms) {
                foreach ($emailTerms as $t) {
                    $w->orWhere('u.email', 'like', "%{$t}%");
                }
            });
        }

        // folder dropdown filter (id multi)
        $folderIds = $toIntList($request->query('user_folder_id'));
        if (!empty($folderIds)) {
            $q->whereIn('u.user_folder_id', $folderIds);
        }

        // folder title filter (optional)
        $folderTitles = $toStrList($request->query('folder_title'));
        if (!empty($folderTitles) && $hasFolderTable) {
            $q->where(function ($w) use ($folderTitles) {
                foreach ($folderTitles as $t) {
                    $w->orWhere('uf.title', 'like', "%{$t}%");
                }
            });
        }

        // search q / search
        $txt = $clean($request->query('q', null));
        $alt = $clean($request->query('search', null));
        $search = $txt ?? $alt;

        if ($search !== null) {
            $q->where(function ($w) use ($search) {
                $w->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%")
                  ->orWhere('dg.title', 'like', "%{$search}%")
                  ->orWhere('dgr.uuid', 'like', "%{$search}%");
            });
        }

        // attempt_status multi
        $attemptStatus = $toStrList($request->query('attempt_status'));
        if (!empty($attemptStatus)) {
            $q->whereIn('dgr.status', $attemptStatus);
        }

        // attempt_no multi
        $attemptNos = $toIntList($request->query('attempt_no'));
        if (!empty($attemptNos)) {
            $q->whereIn('dgr.attempt_no', $attemptNos);
        }

        // publish_to_student filter (admin/instructor only)
        if ($hasPublish && $role !== 'student') {
            $pub = $toBool01($request->query('publish_to_student'));
            if ($pub !== null) {
                $q->where('dgr.publish_to_student', $pub);
            }
        }

        // date range (safe)
        $from = $clean($request->query('from'));
        $to   = $clean($request->query('to'));

        if ($from !== null || $to !== null) {
            try {
                $start = $from ? Carbon::parse($from)->startOfDay() : null;
                $end   = $to   ? Carbon::parse($to)->endOfDay() : null;

                if ($start && $end) {
                    if ($start->gt($end)) { $tmp = $start; $start = $end; $end = $tmp; }
                    $q->whereBetween('dgr.created_at', [$start, $end]);
                } elseif ($start) {
                    $q->where('dgr.created_at', '>=', $start);
                } elseif ($end) {
                    $q->where('dgr.created_at', '<=', $end);
                }
            } catch (\Throwable $e) {}
        }

        // ✅ min/max percentage (NOW REALLY WORKS + swap safety)
        $minPct = $toFloat($request->query('min_percentage'));
        $maxPct = $toFloat($request->query('max_percentage'));

        if ($minPct !== null && $maxPct !== null) {
            if ($minPct > $maxPct) { $tmp = $minPct; $minPct = $maxPct; $maxPct = $tmp; }
            $q->whereRaw("$accExpr BETWEEN ? AND ?", [$minPct, $maxPct]);
        } else {
            if ($minPct !== null) $q->whereRaw("$accExpr >= ?", [$minPct]);
            if ($maxPct !== null) $q->whereRaw("$accExpr <= ?", [$maxPct]);
        }

        /*
        |----------------------------------------------------------
        | ✅ Sorting
        |----------------------------------------------------------
        */
        $sort = (string)$request->query('sort', '-result_created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $key  = ltrim($sort, '-');

        $sortMap = [
            'student_name'      => 'u.name',
            'student_email'     => 'u.email',
            'game_title'        => 'dg.title',
            'score'             => 'dgr.score',
            'accuracy'          => DB::raw($accExpr),
            'attempt_no'        => 'dgr.attempt_no',
            'result_created_at' => 'dgr.created_at',
            'publish_to_student'=> $hasPublish ? 'dgr.publish_to_student' : 'dgr.created_at',
            'folder_title'      => $hasFolderTable ? 'uf.title' : 'u.name',
        ];

        $orderCol = $sortMap[$key] ?? 'dgr.created_at';

        // ✅ stable ordering (pagination safe)
        $q->orderBy($orderCol, $dir)->orderBy('dgr.id', 'desc');

        /*
        |----------------------------------------------------------
        | ✅ No pagination support
        |----------------------------------------------------------
        */
        if ($request->boolean('paginate') === false || $request->query('paginate') === 'false') {
            $rows = $q->get();

            $items = collect($rows)->map(function ($r) use ($hasPublish) {
                $accuracy = isset($r->accuracy_pct) ? (float)$r->accuracy_pct : (round(((float)($r->score ?? 0)) * 100, 2));

                return [
                    'student' => [
                        'id'    => (int)($r->student_id ?? 0),
                        'uuid'  => (string)($r->student_uuid ?? ''),
                        'name'  => (string)($r->student_name ?? ''),
                        'email' => (string)($r->student_email ?? ''),

                        'user_folder_id'   => $r->user_folder_id ?? null,
                        'folder_id'        => $r->folder_id ?? null,
                        'folder_title'     => $r->folder_title ?? null,
                        'folder_name'      => $r->folder_title ?? null,
                        'user_folder_name' => $r->folder_title ?? null,
                    ],

                    'game' => [
                        'id'     => (int)($r->game_id ?? 0),
                        'uuid'   => (string)($r->game_uuid ?? ''),
                        'title'  => (string)($r->game_title ?? ''),
                        'status' => (string)($r->game_status ?? ''),
                    ],

                    'attempt' => [
                        'status' => (string)($r->attempt_status ?? ''),
                    ],

                    'result' => [
                        'id'                => (int)($r->result_id ?? 0),
                        'uuid'              => (string)($r->result_uuid ?? ''),
                        'attempt_no'        => (int)($r->attempt_no ?? 0),
                        'score'             => (int)($r->score ?? 0),
                        'accuracy'          => $accuracy,
                        'publish_to_student'=> $hasPublish ? (int)($r->publish_to_student ?? 0) : 0,

                        'created_at'        => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
                        'result_created_at' => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
                    ],
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => null,
            ]);
        }

        /*
        |----------------------------------------------------------
        | ✅ Pagination (DISTINCT count safety)
        |----------------------------------------------------------
        */
        $perPage = max(1, min(100, (int)$request->query('per_page', 20)));
        $page    = max(1, (int)$request->query('page', 1));

        $base  = clone $q;
        $total = (clone $base)->distinct()->count('dgr.id');

        $rows = (clone $base)->forPage($page, $perPage)->get();

        /*
        |----------------------------------------------------------
        | ✅ Transform for frontend (Bubble-style compatible)
        |----------------------------------------------------------
        */
        $items = collect($rows)->map(function ($r) use ($hasPublish) {
            // ✅ always match SQL-calculated accuracy
            $accuracy = isset($r->accuracy_pct)
                ? (float)$r->accuracy_pct
                : round(((float)($r->score ?? 0)) * 100, 2);

            return [
                'student' => [
                    'id'    => (int)($r->student_id ?? 0),
                    'uuid'  => (string)($r->student_uuid ?? ''),
                    'name'  => (string)($r->student_name ?? ''),
                    'email' => (string)($r->student_email ?? ''),

                    'user_folder_id'   => $r->user_folder_id ?? null,
                    'folder_id'        => $r->folder_id ?? null,
                    'folder_title'     => $r->folder_title ?? null,
                    'folder_name'      => $r->folder_title ?? null,
                    'user_folder_name' => $r->folder_title ?? null,
                ],

                'game' => [
                    'id'     => (int)($r->game_id ?? 0),
                    'uuid'   => (string)($r->game_uuid ?? ''),
                    'title'  => (string)($r->game_title ?? ''),
                    'status' => (string)($r->game_status ?? ''),
                ],

                'attempt' => [
                    'status' => (string)($r->attempt_status ?? ''),
                ],

                'result' => [
                    'id'                => (int)($r->result_id ?? 0),
                    'uuid'              => (string)($r->result_uuid ?? ''),
                    'attempt_no'        => (int)($r->attempt_no ?? 0),
                    'score'             => (int)($r->score ?? 0),
                    'accuracy'          => $accuracy,
                    'publish_to_student'=> $hasPublish ? (int)($r->publish_to_student ?? 0) : 0,

                    'created_at'        => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
                    'result_created_at' => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
                ],
            ];
        })->values();

        $lastPage = (int) ceil($total / max($perPage, 1));

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

public function folderOptions(Request $request)
{
    try {
        if (!Schema::hasTable('user_folders')) {
            return response()->json(['success'=>true,'data'=>[]]);
        }

        $rows = DB::table('user_folders')
            ->when(Schema::hasColumn('user_folders','deleted_at'), fn($q)=>$q->whereNull('deleted_at'))
            ->orderBy('title','asc')
            ->get(['id','title']);

        return response()->json([
            'success' => true,
            'data' => $rows
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to load folder options'
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
    // ✅ Determine status from GAME + PATH + TIME (don’t trust client)
$timeTaken = (int) data_get($decoded, 'timing.time_taken_ms', 0);
$clientStatus = (string) ($decoded['status'] ?? $request->input('status') ?? 'in_progress');

// Decode grid_json (can be string or array depending on DB casting)
$gridArr = $game->grid_json;
if (is_string($gridArr)) {
    $gridArr = json_decode($gridArr, true);
}
if (!is_array($gridArr)) $gridArr = [];

// Find key + door cell ids from grid
$keyId = null;
$doorId = null;
$keyIds = [];

foreach ($gridArr as $cell) {
    if (!is_array($cell)) continue;
    if (!empty($cell['is_key'])) {
        $keyIds[] = (int) ($cell['id'] ?? 0);
    }
    if (!empty($cell['is_door']) && $doorId === null) {
        $doorId = (int) ($cell['id'] ?? 0);
    }
}

// Support 1-key game (your current case)
$keyId = count($keyIds) ? $keyIds[0] : null;

// Get PATH safely
$path = is_array($decoded['path'] ?? null) ? array_map('intval', $decoded['path']) : [];
$endPathCell = !empty($path) ? (int) end($path) : 0;

// ✅ reached door?
$reachedDoor = ($doorId !== null && $endPathCell === (int)$doorId);

// ✅ key picked?
$pickedKey = ($keyId !== null && in_array((int)$keyId, $path, true));

// ✅ key BEFORE door in path
$keyPos  = $pickedKey ? array_search((int)$keyId, $path, true) : false;
$doorPos = $reachedDoor ? array_search((int)$doorId, $path, true) : false;
$keyBeforeDoor = ($keyPos !== false && $doorPos !== false && $keyPos < $doorPos);

// ✅ time check
$limitMs = (int) ($game->time_limit_sec ?? 0) * 1000;
$withinTime = ($limitMs <= 0) ? true : ($timeTaken <= $limitMs);

// ✅ final WIN rule
$isWin = $withinTime && $reachedDoor && $pickedKey && $keyBeforeDoor;

// ✅ timeout overrides everything
if ($clientStatus === 'timeout') {
    $status = 'timeout';
} elseif ($limitMs > 0 && $timeTaken > $limitMs) {
    $status = 'timeout';
} else {
    $status = $isWin ? 'win' : 'fail';
}

// ✅ score
$score = ($status === 'win') ? 1 : 0;

// (optional) patch these fields for consistency / debugging
$decoded['door_cell'] = (int) ($doorId ?? 0);
$decoded['user_end_cell'] = (int) ($endPathCell ?? 0);
$decoded['keys_total'] = (int) count($keyIds);
$decoded['keys_collected'] = ($keyId !== null && $pickedKey) ? [$keyId] : [];


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
    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
    }

    $q = DB::table('door_game_results as r')
        ->join('door_game as g', 'g.id', '=', 'r.door_game_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->whereNull('r.deleted_at')
        ->whereNull('g.deleted_at')
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.door_game_id',
            'r.user_id',
            'r.attempt_no',
            'r.score',
            'r.time_taken_ms',
            'r.status',
            'r.user_answer_json',
            'r.created_at as result_created_at',

            'g.id as game_id',
            'g.uuid as game_uuid',
            'g.title as game_title',
            'g.description as game_description',
            'g.instructions_html',
            'g.show_solution_after',
            'g.grid_dim',
            'g.grid_json',
            'g.max_attempts',
            'g.time_limit_sec',
            'g.status as game_status',

            'u.uuid as student_uuid',
            'u.name as student_name',
            'u.email as student_email',
        ]);

    $this->applyIdOrUuidWhere($q, 'r.id', 'r.uuid', $resultKey);
    $row = $q->first();

    if (!$row) {
        return response()->json(['success'=>false,'message'=>'Result not found'], 404);
    }

    // ✅ student can only view own result
    if ($role === 'student' && (int)$row->user_id !== $userId) {
        return response()->json(['success'=>false,'message'=>'Forbidden'], 403);
    }

    // decode game grid + user snapshot
    $grid = $this->jsonSafe($row->grid_json, []);
    $answer = $this->jsonSafe($row->user_answer_json, []);

    return response()->json([
        'success' => true,
        'game' => [
            'id' => (int)$row->game_id,
            'uuid' => (string)$row->game_uuid,
            'title' => (string)$row->game_title,
            'description' => (string)($row->game_description ?? ''),
            'instructions_html' => (string)($row->instructions_html ?? ''),
            'show_solution_after' => (string)($row->show_solution_after ?? 'after_finish'),
            'grid_dim' => (int)($row->grid_dim ?? 3),
            'grid_json' => $grid,
            'max_attempts' => (int)($row->max_attempts ?? 1),
            'time_limit_sec' => (int)($row->time_limit_sec ?? 30),
            'status' => (string)($row->game_status ?? 'active'),
        ],
        'result' => [
            'result_id' => (int)$row->result_id,
            'result_uuid' => (string)$row->result_uuid,
            'user_id' => (int)$row->user_id,
            'attempt_no' => (int)($row->attempt_no ?? 1),
            'score' => (int)($row->score ?? 0),
            'time_taken_ms' => (int)($row->time_taken_ms ?? 0),
            'status' => (string)($row->status ?? ''),
            'result_created_at' => $row->result_created_at
                ? Carbon::parse($row->result_created_at)->toDateTimeString()
                : null,
            'user_answer' => $answer, // decoded
        ],
        'student' => [
            'id' => (int)$row->user_id,
            'uuid' => (string)($row->student_uuid ?? ''),
            'name' => (string)($row->student_name ?? ''),
            'email' => (string)($row->student_email ?? ''),
        ],
    ], 200);
}
public function resultDetailForInstructor(Request $request, string $resultKey)
{
    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);
    }

    $q = DB::table('door_game_results as r')
        ->join('door_game as g', 'g.id', '=', 'r.door_game_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->whereNull('r.deleted_at')
        ->whereNull('g.deleted_at')
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.door_game_id',
            'r.user_id',
            'r.attempt_no',
            'r.score',
            'r.time_taken_ms',
            'r.status',
            'r.user_answer_json',
            'r.created_at as result_created_at',

            'g.id as game_id',
            'g.uuid as game_uuid',
            'g.title as game_title',
            'g.description as game_description',
            'g.grid_dim',
            'g.grid_json',
            'g.time_limit_sec',

            'u.uuid as student_uuid',
            'u.name as student_name',
            'u.email as student_email',
        ]);

    $this->applyIdOrUuidWhere($q, 'r.id', 'r.uuid', $resultKey);
    $row = $q->first();

    if (!$row) {
        return response()->json(['success'=>false,'message'=>'Result not found'], 404);
    }

    // ✅ examiner/instructor can only view if assigned to this door game
    if (in_array($role, ['instructor','examiner'], true)) {
        if (!$this->userAssignedToDoorGame($userId, (int)$row->game_id)) {
            return response()->json(['success'=>false,'message'=>'You are not assigned to this door game'], 403);
        }
    }

    $grid = $this->jsonSafe($row->grid_json, []);
    $answer = $this->jsonSafe($row->user_answer_json, []);

    return response()->json([
        'success' => true,
        'game' => [
            'id' => (int)$row->game_id,
            'uuid' => (string)$row->game_uuid,
            'title' => (string)$row->game_title,
            'description' => (string)($row->game_description ?? ''),
            'grid_dim' => (int)($row->grid_dim ?? 3),
            'grid_json' => $grid,
            'time_limit_sec' => (int)($row->time_limit_sec ?? 30),
        ],
        'result' => [
            'result_id' => (int)$row->result_id,
            'result_uuid' => (string)$row->result_uuid,
            'score' => (int)($row->score ?? 0),
            'attempt_no' => (int)($row->attempt_no ?? 1),
            'time_taken_ms' => (int)($row->time_taken_ms ?? 0),
            'status' => (string)($row->status ?? ''),
            'result_created_at' => $row->result_created_at
                ? Carbon::parse($row->result_created_at)->toDateTimeString()
                : null,
            'user_answer' => $answer,
        ],
        'student' => [
            'id' => (int)$row->user_id,
            'uuid' => (string)($row->student_uuid ?? ''),
            'name' => (string)($row->student_name ?? ''),
            'email' => (string)($row->student_email ?? ''),
        ],
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

    $game = $this->doorGameByKey($gameKey);
    if (!$game) {
        return response()->json(['success'=>false,'message'=>'Door game not found'], 404);
    }

    // examiner/instructor must be assigned to this game
    if (in_array($role, ['instructor','examiner'], true)) {
        if (!$this->userAssignedToDoorGame($userId, (int)$game->id)) {
            return response()->json(['success'=>false,'message'=>'You are not assigned to this door game'], 403);
        }
    }

    // ==========================
    // Assigned Students (ONLY students)
    // ==========================
    $assignedUsersQ = DB::table('user_door_game_assignments as a')
        ->join('users as u', 'u.id', '=', 'a.user_id')
        ->where('a.door_game_id', (int)$game->id);

    // soft delete safe + status safe
    try {
        if (\Illuminate\Support\Facades\Schema::hasColumn('user_door_game_assignments', 'deleted_at')) {
            $assignedUsersQ->whereNull('a.deleted_at');
        }

        // ✅ do not hard-force status='active' because many DBs store: assigned/enabled/1 etc
        if (\Illuminate\Support\Facades\Schema::hasColumn('user_door_game_assignments', 'status')) {
            $assignedUsersQ->where(function($w){
                $w->whereNull('a.status')
                  ->orWhereIn('a.status', ['active','assigned','enabled',1,'1']);
            });
        }

        // student filter safe
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $assignedUsersQ->whereRaw("LOWER(u.role) = 'student'");
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role_short_form')) {
            $assignedUsersQ->whereRaw("LOWER(u.role_short_form) = 'student'");
        }
    } catch (\Throwable $e) {
        // ignore
    }

    $assignedStudentIds = $assignedUsersQ->pluck('u.id')->map(fn($x)=>(int)$x)->values()->all();
    $totalAssignedStudents = count($assignedStudentIds);

    // ==========================
    // Attempts (results) - ONLY students
    // ==========================
    $attemptsQ = DB::table('door_game_results as r')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->where('r.door_game_id', (int)$game->id);

    // soft delete safe
    try {
        if (\Illuminate\Support\Facades\Schema::hasColumn('door_game_results', 'deleted_at')) {
            $attemptsQ->whereNull('r.deleted_at');
        }
    } catch (\Throwable $e) {}

    // ✅ only role==student results
    try {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $attemptsQ->whereRaw("LOWER(u.role) = 'student'");
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role_short_form')) {
            $attemptsQ->whereRaw("LOWER(u.role_short_form) = 'student'");
        }
    } catch (\Throwable $e) {}

    // ✅ if assignment list exists, restrict to assigned users
    // ✅ if assignment list empty, DO NOT force 1=0 (because results still exist)
    if ($totalAssignedStudents > 0) {
        $attemptsQ->whereIn('r.user_id', $assignedStudentIds);
    }

    // search (name/email)
    $qText = trim((string)$request->query('q', ''));
    if ($qText !== '') {
        $attemptsQ->where(function($w) use ($qText){
            $w->where('u.name', 'like', "%{$qText}%")
              ->orWhere('u.email','like', "%{$qText}%");
        });
    }

    // order safe
    try {
        if (\Illuminate\Support\Facades\Schema::hasColumn('door_game_results', 'created_at')) {
            $attemptsQ->orderByDesc('r.created_at');
        } else {
            $attemptsQ->orderByDesc('r.id');
        }
    } catch (\Throwable $e) {
        $attemptsQ->orderByDesc('r.id');
    }

    // ✅ user_answer_json safe select
    $answerCol = \Illuminate\Support\Facades\Schema::hasColumn('door_game_results', 'user_answer_json')
        ? 'r.user_answer_json'
        : (\Illuminate\Support\Facades\Schema::hasColumn('door_game_results', 'user_answer')
            ? 'r.user_answer'
            : DB::raw("'' as user_answer_json"));

    $attempts = $attemptsQ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.user_id as student_id',
            'u.name as student_name',
            'u.email as student_email',
            'r.attempt_no',
            'r.score',
            'r.time_taken_ms',
            'r.status',
            $answerCol,
            'r.created_at as result_created_at',
        ])
        ->get()
        ->map(function($a){
            $raw = (string)($a->user_answer_json ?? '');

            // ✅ decoded json for UI
            $decoded = [];
            try {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) $decoded = [];
            } catch (\Throwable $e) {
                $decoded = [];
            }

            return [
                'result_id'         => (int)$a->result_id,
                'result_uuid'       => (string)($a->result_uuid ?? ''),
                'student_id'        => (int)$a->student_id,
                'student_name'      => (string)($a->student_name ?? ''),
                'student_email'     => (string)($a->student_email ?? ''),
                'attempt_no'        => (int)($a->attempt_no ?? 1),
                'score'             => (int)($a->score ?? 0),
                'percentage'        => (int)($a->score ?? 0) * 100,
                'time_taken_ms'     => (int)($a->time_taken_ms ?? 0),
                'status'            => (string)($a->status ?? ''),

                // ✅ send both (your UI can use any)
                'user_answer_json'  => $raw,
                'user_answer'       => $decoded,

                'result_created_at' => $a->result_created_at
                    ? \Carbon\Carbon::parse($a->result_created_at)->toDateTimeString()
                    : null,
            ];
        })
        ->values();

    $totalAttempts   = $attempts->count();
    $uniqueAttempted = $attempts->pluck('student_id')->unique()->count();

    // ✅ fallback for metric if assignment table has issues
    if ($totalAssignedStudents <= 0) {
        $totalAssignedStudents = $uniqueAttempted;
    }

    return response()->json([
        'success' => true,
        'data' => [
            'game' => [
                'id'    => (int)$game->id,
                'uuid'  => (string)$game->uuid,
                'title' => (string)$game->title,
                'time_limit_sec' => isset($game->time_limit_sec) ? (int)$game->time_limit_sec : null,
                'grid_dim' => isset($game->grid_dim) ? (int)$game->grid_dim : null,
            ],
            'stats' => [
                'total_attempts'          => (int)$totalAttempts,
                'unique_attempted'        => (int)$uniqueAttempted,
                'total_assigned_students' => (int)$totalAssignedStudents,
            ],
            'attempts' => $attempts,
        ]
    ], 200);
}

private function doorGameTableName(): ?string
{
    try {
        if (Schema::hasTable('door_game')) return 'door_game';     // your current join uses this name
        if (Schema::hasTable('door_games')) return 'door_games';   // fallback (common naming)
    } catch (\Throwable $e) {}

    return null;
}

private function doorGameByKey(string $key)
{
    $key = trim($key);
    if ($key === '') return null;

    $table = $this->doorGameTableName();
    if (!$table) return null;

    try {
        $q = DB::table($table);

        // soft delete safe
        if (Schema::hasColumn($table, 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        // detect uuid / id / slug
        if (ctype_digit($key)) {
            $q->where('id', (int)$key);
        } elseif (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $key)) {
            // UUID
            if (Schema::hasColumn($table, 'uuid')) {
                $q->where('uuid', $key);
            } else {
                return null;
            }
        } else {
            // slug / key fallback
            if (Schema::hasColumn($table, 'slug')) {
                $q->where('slug', $key);
            } elseif (Schema::hasColumn($table, 'game_key')) {
                $q->where('game_key', $key);
            } else {
                // last fallback
                $q->where('title', $key);
            }
        }

        return $q->first();
    } catch (\Throwable $e) {
        return null;
    }
}
private function userAssignedToDoorGame(int $userId, int $doorGameId): bool
{
    if ($userId <= 0 || $doorGameId <= 0) return false;    

    try {
        $q = DB::table('user_door_game_assignments')
            ->where('user_id', $userId)
            ->where('door_game_id', $doorGameId);

        // soft delete safe
        if (Schema::hasColumn('user_door_game_assignments', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        // status safe
        if (Schema::hasColumn('user_door_game_assignments', 'status')) {
            $q->where('status', 'active');
        }

        return $q->exists();
    } catch (\Throwable $e) {
        return false;
    }
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
/* ============================================================
| Publish helpers (DB based, Bubble-style)
|============================================================ */

private function boolToTiny($v): int
{
    if (is_bool($v)) return $v ? 1 : 0;
    if (is_numeric($v)) return ((int)$v) ? 1 : 0;
    $s = strtolower(trim((string)$v));
    return in_array($s, ['1','true','yes','y','on'], true) ? 1 : 0;
}

/**
 * ✅ Find result by uuid or numeric id (soft-delete safe)
 */
private function doorResultByKey(string $key)
{
    $q = DB::table('door_game_results')
        ->whereNull('deleted_at');

    if (ctype_digit($key)) {
        $q->where('id', (int)$key);
    } else {
        $q->where('uuid', $key);
    }

    return $q->first();
}

/**
 * ✅ Common publish/unpublish handler (single result)
 */
private function setPublishToStudent(Request $request, string $resultKey, int $to)
{
    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    // ✅ only admin/examiner/instructor can publish
    if (!in_array($role, ['admin','superadmin','super_admin','director','examiner','instructor'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    // ✅ must exist column
    if (!Schema::hasColumn('door_game_results', 'publish_to_student')) {
        return response()->json([
            'success' => false,
            'message' => 'publish_to_student column missing in DB'
        ], 500);
    }

    $row = $this->doorResultByKey($resultKey);

    if (!$row) {
        return response()->json([
            'success' => false,
            'message' => 'Result not found'
        ], 404);
    }

    // ✅ examiner/instructor can publish only if assigned to that game
    if (in_array($role, ['examiner','instructor'], true)) {
        if (!$this->userAssignedToDoorGame($userId, (int)$row->door_game_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this door game'
            ], 403);
        }
    }

    $to = (int)$to;

    DB::table('door_game_results')
        ->where('id', (int)$row->id)
        ->update([
            'publish_to_student' => $to,
            'updated_at'         => now(),
            'updated_at_ip'      => $request->ip(),
        ]);

    return response()->json([
        'success' => true,
        'message' => $to ? 'Published to student' : 'Unpublished from student',
        'data' => [
            'result_id'          => (int)$row->id,
            'result_uuid'        => (string)$row->uuid,
            'publish_to_student' => $to,
        ]
    ], 200);
}

/**
 * ✅ Single publish
 */
public function publishResultToStudent(Request $request, string $resultKey)
{
    return $this->setPublishToStudent($request, $resultKey, 1);
}

/**
 * ✅ Single unpublish
 */
public function unpublishResultToStudent(Request $request, string $resultKey)
{
    return $this->setPublishToStudent($request, $resultKey, 0);
}

/**
 * ✅ Bulk publish/unpublish (one API for both)
 * Payload:
 * {
 *   "result_uuids": ["uuid1","uuid2"],
 *   "publish_to_student": 1
 * }
 */
public function bulkPublishAny(Request $request)
{
    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    if (!in_array($role, ['admin','superadmin','super_admin','director','examiner','instructor'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    if (!Schema::hasColumn('door_game_results', 'publish_to_student')) {
        return response()->json([
            'success' => false,
            'message' => 'publish_to_student column missing in DB'
        ], 500);
    }

    $validator = Validator::make($request->all(), [
        'result_uuids'        => ['required','array','min:1'],
        'result_uuids.*'      => ['required','string'],
        'publish_to_student'  => ['required'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $uuids = array_values(array_filter((array)$request->input('result_uuids', [])));
    $to    = $this->boolToTiny($request->input('publish_to_student'));

    // ✅ base query
    $q = DB::table('door_game_results')
        ->whereNull('deleted_at')
        ->whereIn('uuid', $uuids);

    // ✅ examiner/instructor restrictions
    if (in_array($role, ['examiner','instructor'], true)) {
        $assignedGameIds = DB::table('user_door_game_assignments')
            ->where('user_id', $userId)
            ->when(Schema::hasColumn('user_door_game_assignments','deleted_at'), fn($x)=>$x->whereNull('deleted_at'))
            ->pluck('door_game_id')
            ->map(fn($x)=>(int)$x)
            ->values()
            ->all();

        if (!empty($assignedGameIds)) {
            $q->whereIn('door_game_id', $assignedGameIds);
        } else {
            // no assignments => block
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to any door games'
            ], 403);
        }
    }

    $affected = $q->update([
        'publish_to_student' => $to,
        'updated_at'         => now(),
        'updated_at_ip'      => $request->ip(),
    ]);

    // return updated rows for UI
    $updated = DB::table('door_game_results')
        ->whereIn('uuid', $uuids)
        ->whereNull('deleted_at')
        ->get(['id','uuid','publish_to_student','updated_at'])
        ->map(fn($r)=>[
            'result_id'          => (int)$r->id,
            'result_uuid'        => (string)$r->uuid,
            'publish_to_student' => (int)$r->publish_to_student,
            'updated_at'         => $r->updated_at ? Carbon::parse($r->updated_at)->toDateTimeString() : null,
        ])->values();

    return response()->json([
        'success'  => true,
        'message'  => $to ? 'Bulk published to students' : 'Bulk unpublished from students',
        'affected' => (int)$affected,
        'data'     => $updated,
    ], 200);
}
/**
 * GET /api/door-game/result/export
 *
 * Export filtered door game results as CSV
 * 
 * Supports all the same filters as index():
 *  - door_game_id, game_uuid, student_email, user_folder_id, folder_title
 *  - q, search, from, to
 *  - publish_to_student (0/1)
 *  - min_percentage, max_percentage
 *  - attempt_status, attempt_no
 *  - sort
 * 
 * Exported columns:
 *  - Student Name
 *  - Email
 *  - Phone No
 *  - User Folder Name
 *  - Game Title
 *  - Percentage (%)
 *  - Score
 *  - Attempt Number
 *  - Time Taken (sec)
 *  - Efficiency (%)
 */
public function export(Request $request)
{
    Log::info('DoorGameResult.export: start', [
        'ip' => $request->ip(),
        'query' => $request->query(),
    ]);

    try {
        // ✅ token-safe actor
        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        /*
        |----------------------------------------------------------------------
        | ✅ Helpers (same as index)
        |----------------------------------------------------------------------
        */
        $clean = function ($v) {
            if ($v === null) return null;

            if (is_string($v)) {
                $v = trim($v);
                if ($v === '') return null;

                $low = strtolower($v);
                if (in_array($low, ['all', 'any', 'null', 'undefined', 'none'], true)) return null;
                return $v;
            }

            return $v;
        };

        $toStrList = function ($v) use ($clean) {
            if ($v === null) return [];

            $arr = is_array($v)
                ? $v
                : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);

            $out = [];
            foreach ($arr as $item) {
                $item = $clean($item);
                if ($item !== null) $out[] = (string)$item;
            }

            return array_values(array_unique($out));
        };

        $toIntList = function ($v) use ($clean) {
            if ($v === null) return [];

            $arr = is_array($v)
                ? $v
                : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);

            $out = [];
            foreach ($arr as $item) {
                $item = $clean($item);
                if ($item !== null && is_numeric($item)) {
                    $n = (int)$item;
                    if ($n > 0) $out[] = $n;
                }
            }

            return array_values(array_unique($out));
        };

        $toBool01 = function ($v) use ($clean) {
            $v = $clean($v);
            if ($v === null) return null;

            if (in_array((string)$v, ['0', '1'], true)) return (int)$v;

            $b = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($b === null) return null;

            return $b ? 1 : 0;
        };

        $toFloat = function ($v) use ($clean) {
            $v = $clean($v);
            if ($v === null) return null;
            if (!is_numeric($v)) return null;
            return (float)$v;
        };

        /*
        |----------------------------------------------------------------------
        | ✅ Column checks (same as index)
        |----------------------------------------------------------------------
        */
        $hasPublish = false;
        try {
            $hasPublish = Schema::hasColumn('door_game_results', 'publish_to_student');
        } catch (\Throwable $e) {
            $hasPublish = false;
        }

        $hasFolderTable = false;
        $hasFolderDeletedAt = false;

        try {
            $hasFolderTable = Schema::hasTable('user_folders');
            if ($hasFolderTable) {
                $hasFolderDeletedAt = Schema::hasColumn('user_folders', 'deleted_at');
            }
        } catch (\Throwable $e) {
            $hasFolderTable = false;
            $hasFolderDeletedAt = false;
        }

        $hasUserDeletedAt = false;
        try {
            $hasUserDeletedAt = Schema::hasColumn('users', 'deleted_at');
        } catch (\Throwable $e) {
            $hasUserDeletedAt = false;
        }

        /*
        |----------------------------------------------------------------------
        | ✅ Base query (same as index)
        |----------------------------------------------------------------------
        */
        $q = DB::table('door_game_results as dgr')
            ->join('door_game as dg', 'dgr.door_game_id', '=', 'dg.id')
            ->join('users as u', 'dgr.user_id', '=', 'u.id')
            ->whereNull('dgr.deleted_at')
            ->whereNull('dg.deleted_at');

        if ($hasUserDeletedAt) {
            $q->whereNull('u.deleted_at');
        }

        // ✅ JOIN folder if exists
        if ($hasFolderTable) {
            $q->leftJoin('user_folders as uf', function ($j) use ($hasFolderDeletedAt) {
                $j->on('uf.id', '=', 'u.user_folder_id');
                if ($hasFolderDeletedAt) {
                    $j->whereNull('uf.deleted_at');
                }
            });
        }

        /*
        |----------------------------------------------------------------------
        | ✅ Accuracy % expression
        |----------------------------------------------------------------------
        */
        $accExpr = '(COALESCE(dgr.score,0) * 100.0)';

        /*
        |----------------------------------------------------------------------
        | ✅ SELECT columns for export
        |----------------------------------------------------------------------
        */
        $select = [
            'u.name as student_name',
            'u.email as student_email',
            'u.phone_number as phone_no',


            'dg.title as game_title',
            'dg.time_limit_sec',

            'dgr.score',
            'dgr.attempt_no',
            'dgr.time_taken_ms',

            DB::raw("ROUND($accExpr, 2) as accuracy_pct"),
        ];

        // folder title safe
        if ($hasFolderTable) {
            $select[] = 'uf.title as folder_title';
        } else {
            $select[] = DB::raw('NULL as folder_title');
        }

        $q->select($select);

        /*
        |----------------------------------------------------------
        | ✅ Student visibility rule
        |----------------------------------------------------------
        */
        if ($role === 'student') {
            $q->where('dgr.user_id', $userId);
            if ($hasPublish) {
                $q->where('dgr.publish_to_student', 1);
            }
        }

        /*
        |----------------------------------------------------------
        | ✅ Apply all filters (same as index)
        |----------------------------------------------------------
        */

        // door_game_id (mixed)
        $doorGameKeys = $toStrList($request->query('door_game_id'));
        if (!empty($doorGameKeys)) {
            $ids = [];
            $uuids = [];

            foreach ($doorGameKeys as $v) {
                if (is_numeric($v)) $ids[] = (int)$v;
                else $uuids[] = (string)$v;
            }

            $ids = array_values(array_unique($ids));
            $uuids = array_values(array_unique($uuids));

            $q->where(function ($w) use ($ids, $uuids) {
                $hasAny = false;
                if (!empty($ids)) {
                    $w->orWhereIn('dg.id', $ids);
                    $hasAny = true;
                }
                if (!empty($uuids)) {
                    $w->orWhereIn('dg.uuid', $uuids);
                    $hasAny = true;
                }
                if (!$hasAny) {
                    $w->whereRaw('1=1');
                }
            });
        }

        // game_uuid multi
        $gameUuids = $toStrList($request->query('game_uuid'));
        if (!empty($gameUuids)) {
            $q->whereIn('dg.uuid', $gameUuids);
        }

        // student_email multi terms (OR)
        $emailTerms = $toStrList($request->query('student_email'));
        if (!empty($emailTerms)) {
            $q->where(function ($w) use ($emailTerms) {
                foreach ($emailTerms as $t) {
                    $w->orWhere('u.email', 'like', "%{$t}%");
                }
            });
        }

        // folder dropdown filter (id multi)
        $folderIds = $toIntList($request->query('user_folder_id'));
        if (!empty($folderIds)) {
            $q->whereIn('u.user_folder_id', $folderIds);
        }

        // folder title filter
        $folderTitles = $toStrList($request->query('folder_title'));
        if (!empty($folderTitles) && $hasFolderTable) {
            $q->where(function ($w) use ($folderTitles) {
                foreach ($folderTitles as $t) {
                    $w->orWhere('uf.title', 'like', "%{$t}%");
                }
            });
        }

        // search q / search
        $txt = $clean($request->query('q', null));
        $alt = $clean($request->query('search', null));
        $search = $txt ?? $alt;

        if ($search !== null) {
            $q->where(function ($w) use ($search) {
                $w->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%")
                  ->orWhere('dg.title', 'like', "%{$search}%")
                  ->orWhere('dgr.uuid', 'like', "%{$search}%");
            });
        }

        // attempt_status multi
        $attemptStatus = $toStrList($request->query('attempt_status'));
        if (!empty($attemptStatus)) {
            $q->whereIn('dgr.status', $attemptStatus);
        }

        // attempt_no multi
        $attemptNos = $toIntList($request->query('attempt_no'));
        if (!empty($attemptNos)) {
            $q->whereIn('dgr.attempt_no', $attemptNos);
        }

        // publish_to_student filter (admin/instructor only)
        if ($hasPublish && $role !== 'student') {
            $pub = $toBool01($request->query('publish_to_student'));
            if ($pub !== null) {
                $q->where('dgr.publish_to_student', $pub);
            }
        }

        // date range
        $from = $clean($request->query('from'));
        $to   = $clean($request->query('to'));

        if ($from !== null || $to !== null) {
            try {
                $start = $from ? Carbon::parse($from)->startOfDay() : null;
                $end   = $to   ? Carbon::parse($to)->endOfDay() : null;

                if ($start && $end) {
                    if ($start->gt($end)) { $tmp = $start; $start = $end; $end = $tmp; }
                    $q->whereBetween('dgr.created_at', [$start, $end]);
                } elseif ($start) {
                    $q->where('dgr.created_at', '>=', $start);
                } elseif ($end) {
                    $q->where('dgr.created_at', '<=', $end);
                }
            } catch (\Throwable $e) {}
        }

        // min/max percentage
        $minPct = $toFloat($request->query('min_percentage'));
        $maxPct = $toFloat($request->query('max_percentage'));

        if ($minPct !== null && $maxPct !== null) {
            if ($minPct > $maxPct) { $tmp = $minPct; $minPct = $maxPct; $maxPct = $tmp; }
            $q->whereRaw("$accExpr BETWEEN ? AND ?", [$minPct, $maxPct]);
        } else {
            if ($minPct !== null) $q->whereRaw("$accExpr >= ?", [$minPct]);
            if ($maxPct !== null) $q->whereRaw("$accExpr <= ?", [$maxPct]);
        }

        /*
        |----------------------------------------------------------
        | ✅ Sorting
        |----------------------------------------------------------
        */
        $sort = (string)$request->query('sort', '-result_created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $key  = ltrim($sort, '-');

        $sortMap = [
            'student_name'      => 'u.name',
            'student_email'     => 'u.email',
            'game_title'        => 'dg.title',
            'score'             => 'dgr.score',
            'accuracy'          => DB::raw($accExpr),
            'attempt_no'        => 'dgr.attempt_no',
            'result_created_at' => 'dgr.created_at',
            'folder_title'      => $hasFolderTable ? 'uf.title' : 'u.name',
        ];

        $orderCol = $sortMap[$key] ?? 'dgr.created_at';

        $q->orderBy($orderCol, $dir)->orderBy('dgr.id', 'desc');

        /*
        |----------------------------------------------------------
        | ✅ Fetch all results (no pagination for export)
        |----------------------------------------------------------
        */
        $rows = $q->get();

        if ($rows->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No results found to export',
            ], 404);
        }

        /*
        |----------------------------------------------------------
        | ✅ Generate CSV
        |----------------------------------------------------------
        */
        $filename = 'door_game_results_' . Carbon::now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($rows) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8 Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Headers
            fputcsv($file, [
                'Student Name',
                'Email',
                'Phone No',
                'User Folder Name',
                'Game Title',
                'Percentage (%)',
                'Score',
                'Attempt Number',
                'Time Taken (sec)',
                'Efficiency (%)',
            ]);

            // CSV Data Rows
            foreach ($rows as $r) {
                $percentage = isset($r->accuracy_pct) ? (float)$r->accuracy_pct : 0;
                $timeTakenMs = (int)($r->time_taken_ms ?? 0);
                $timeTakenSec = $timeTakenMs > 0 ? round($timeTakenMs / 1000, 2) : 0;
                
                // Calculate efficiency: (score / time_limit) * 100 if time_limit exists
                $efficiency = 0;
                $timeLimitSec = (int)($r->time_limit_sec ?? 0);
                if ($timeLimitSec > 0 && $timeTakenSec > 0) {
                    // Efficiency = (remaining_time / time_limit) * 100
                    // OR Efficiency = score * (time_limit / time_taken) * 100
                    $efficiency = min(100, round((($timeLimitSec - $timeTakenSec) / $timeLimitSec) * 100, 2));
                    // Alternative formula if you want: score-based efficiency
                    // $efficiency = round(((int)($r->score ?? 0) * 100) / max($timeTakenSec, 1), 2);
                }

                fputcsv($file, [
                    $r->student_name ?? '',
                    $r->student_email ?? '',
                    $r->phone_no ?? '',
                    $r->folder_title ?? '',
                    $r->game_title ?? '',
                    number_format($percentage, 2),
                    (int)($r->score ?? 0),
                    (int)($r->attempt_no ?? 0),
                    $timeTakenSec,
                    number_format($efficiency, 2),
                ]);
            }

            fclose($file);
        };

        Log::info('DoorGameResult.export: success', [
            'rows_exported' => $rows->count(),
            'filename' => $filename,
        ]);

        return response()->stream($callback, 200, $headers);

    } catch (\Throwable $e) {
        Log::error('DoorGameResult.export: exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Server error while exporting results',
        ], 500);
    }
}

}
