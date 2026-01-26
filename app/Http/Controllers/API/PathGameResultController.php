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

class PathGameResultController extends Controller
{
    /* ============================================================
     | Helpers (Role + Actor)
     *============================================================ */

    private function normalizeRole(?string $role): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)$role));
    }

    private function actor(Request $request): array
    {
        // CheckRole middleware usually sets these
        $actor = [
            'role' => $request->attributes->get('auth_role') ?? ($request->user()?->role ?? ''),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? ($request->user()?->id ?? 0)),
        ];

        Log::debug('PathGameResult.actor', $actor);

        return $actor;
    }

    private function actorId(Request $request): int
    {
        $actor = $this->actor($request);
        return (int)($actor['id'] ?? 0);
    }

    private function getClientIp(Request $request): string
    {
        return $request->ip() ?? '0.0.0.0';
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

    private function boolToTiny($v): int
    {
        if (is_bool($v)) return $v ? 1 : 0;
        if (is_numeric($v)) return ((int)$v) ? 1 : 0;
        $s = strtolower(trim((string)$v));
        return in_array($s, ['1', 'true', 'yes', 'y', 'on'], true) ? 1 : 0;
    }

    /* ============================================================
     | Game / Result finders (ID or UUID safe)
     *============================================================ */

    private function findResult(string $idOrUuid)
    {
        return DB::table('path_game_results')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($idOrUuid) {
                $q->where('id', $idOrUuid)->orWhere('uuid', $idOrUuid);
            })
            ->first();
    }

    private function findGame(string $gameKey)
    {
        return DB::table('path_games')
            ->whereNull('deleted_at')
            ->where(function ($q) use ($gameKey) {
                if (ctype_digit($gameKey)) {
                    $q->orWhere('id', (int)$gameKey);
                }
                $q->orWhere('uuid', $gameKey);
            })
            ->first();
    }

    private function pathAssignmentTable(): ?string
    {
        try {
            if (Schema::hasTable('user_path_game_assignments')) return 'user_path_game_assignments';
            if (Schema::hasTable('user_path_games_assignments')) return 'user_path_games_assignments';
        } catch (\Throwable $e) {}
        return null;
    }

    private function userAssignedToPathGame(int $userId, int $pathGameId): bool
    {
        if ($userId <= 0 || $pathGameId <= 0) return false;

        $table = $this->pathAssignmentTable();
        if (!$table) return true; // ✅ if no assignment module exists, allow

        try {
            $q = DB::table($table)
                ->where('user_id', $userId)
                ->where('path_game_id', $pathGameId);

            if (Schema::hasColumn($table, 'deleted_at')) $q->whereNull('deleted_at');

            if (Schema::hasColumn($table, 'status')) {
                $q->where(function ($w) {
                    $w->whereNull('status')->orWhereIn('status', ['active', 'assigned', 'enabled', 1, '1']);
                });
            }

            return $q->exists();
        } catch (\Throwable $e) {
            return true;
        }
    }

    /* ============================================================
     | Score + Validation (your existing logic kept)
     *============================================================ */

    private function normalizeUserAnswerJson(array &$payload, $game = null): void
{
    if (empty($payload['user_answer_json']) || !is_array($payload['user_answer_json'])) {
        $payload['user_answer_json'] = [];
        return;
    }

    $ua = (array)$payload['user_answer_json'];

    // ✅ Only normalize when version 2 replay exists
    $timeline = data_get($ua, 'replay.timeline', null);
    if (!is_array($timeline) || empty($timeline)) {
        // old format, keep as-is
        $payload['user_answer_json'] = $ua;
        return;
    }

    // ✅ sort timeline by t_ms for safety
    usort($timeline, fn($a, $b) => (int)($a['t_ms'] ?? 0) <=> (int)($b['t_ms'] ?? 0));

    $pathNumeric = [];
    $rotationLog = [];
    $eventsMap   = [];

    $currentIndex = (int) data_get($ua, 'replay.start_index', 0);
    $targetIndex  = (int) data_get($ua, 'replay.target_index', 0);

    $hasEarthEvent = false;

    foreach ($timeline as $act) {
        $type = (string)($act['type'] ?? '');
        $tms  = (int)($act['t_ms'] ?? 0);
        $p    = (array)($act['payload'] ?? []);

        if ($type === 'start') {
            $ci = (int)($p['current_index'] ?? $currentIndex);
            $currentIndex = $ci;

            if ($currentIndex > 0 && empty($pathNumeric)) {
                $pathNumeric[] = $currentIndex;
            }
            continue;
        }

        if ($type === 'move') {
            $allowed = (bool)($p['allowed'] ?? true);

            if ($allowed) {
                $to = (int)($p['to'] ?? 0);
                if ($to > 0) {
                    // ensure start exists
                    if (empty($pathNumeric)) {
                        $from = (int)($p['from'] ?? 0);
                        if ($from > 0) $pathNumeric[] = $from;
                    }
                    $pathNumeric[] = $to;
                    $currentIndex = $to;
                }
            }
            continue;
        }

        if ($type === 'rotate') {
            $rotationLog[] = [
                'rotation_step' => (int)($p['rotation_step'] ?? 0),
                'tile_index'    => (int)($p['tile_index'] ?? 0),
                'dir'           => (string)($p['dir'] ?? ''),
                'rotate_by_deg' => (int)($p['rotate_by_deg'] ?? 90),
                'before_deg'    => (int)($p['before_deg'] ?? 0),
                'after_deg'     => (int)($p['after_deg'] ?? 0),
                't_ms'          => $tms,
                'rotation_type' => (string)($p['dir'] ?? ''),
                'rotation_count'=> 1,
            ];
            continue;
        }

        if ($type === 'event') {
            $name = (string)($p['name'] ?? '');
            $at   = (int)($p['at_index'] ?? 0);

            if ($name !== '') {
                $eventsMap[$name] = [
                    'at_index' => $at,
                    't_ms'     => $tms,
                    'meta'     => (array)($p['meta'] ?? []),
                ];
            }

            if ($name === 'earth_reached') {
                $hasEarthEvent = true;
            }

            continue;
        }
    }

    // ✅ fallback start/end indexes
    $startIndex = $pathNumeric[0] ?? (int) data_get($ua, 'replay.start_index', 0);
    $endIndex   = !empty($pathNumeric) ? $pathNumeric[count($pathNumeric) - 1] : $currentIndex;

    // ✅ reached earth logic
    $reachedEarth = $hasEarthEvent || ($targetIndex > 0 && $endIndex === $targetIndex);

    // ✅ Add compatibility keys for evaluateAnswer()
    // Your old evaluator can keep using these
    $ua['final_path'] = $reachedEarth
        ? array_merge($pathNumeric, ['EARTH'])
        : $pathNumeric;

    $ua['rotation_log'] = $rotationLog;
    $ua['events_map']   = $eventsMap;

    // ✅ meta
    $ua['meta'] = array_merge((array)($ua['meta'] ?? []), [
        'start_index'   => $startIndex ?: null,
        'end_index'     => $endIndex ?: null,
        'target_index'  => $targetIndex ?: null,
        'reached_earth' => $reachedEarth,
        'path_numeric'  => $pathNumeric,
        'path_raw'      => $ua['final_path'],
        'grid_dim'      => (int) data_get($ua, 'game.grid_dim', (int)($game->grid_dim ?? 0)),
    ]);

    // ✅ timing fallback
    if (empty($ua['timing']) || !is_array($ua['timing'])) $ua['timing'] = [];
    $ua['timing']['total_ms'] = (int)($payload['time_taken_ms'] ?? 0);

    $payload['user_answer_json'] = $ua;
}


    private function mapResultRow($row)
    {
        if (!$row) return null;

        $row->user_answer_json = is_string($row->user_answer_json)
            ? json_decode($row->user_answer_json, true)
            : $row->user_answer_json;

        return $row;
    }

    private function calculateScore(array $userAnswer, $gameData): int
    {
        // ✅ Your existing placeholder scoring
        $score = 0;
        $correctPath = $userAnswer['correct_path'] ?? false;
        $timeTaken = $userAnswer['time_taken_ms'] ?? 0;

        $gameTimeLimit = ((int)($gameData->time_limit_sec ?? 0)) * 1000;
        if ($gameTimeLimit <= 0 && isset($gameData->time_limit_ms)) $gameTimeLimit = (int)$gameData->time_limit_ms;

        if ($correctPath) {
            $score = 100;

            // Bonus for speed (max 50 points)
            if ($timeTaken > 0 && $gameTimeLimit > 0 && $timeTaken <= $gameTimeLimit) {
                $timeBonus = (int)(50 * (1 - ($timeTaken / $gameTimeLimit)));
                $score += max(0, $timeBonus);
            }
        }

        return $score;
    }

    private function evaluateAnswer(array $userAnswerJson, $gameData, int $timeTakenMs): array
    {
        $gridJson = is_string($gameData->grid_json)
            ? json_decode($gameData->grid_json, true)
            : $gameData->grid_json;

        // time limit in ms
        $timeLimitMs = 0;
        if (isset($gameData->time_limit_ms) && (int)$gameData->time_limit_ms > 0) {
            $timeLimitMs = (int)$gameData->time_limit_ms;
        } else {
            $sec = (int)($gameData->time_limit_sec ?? $gameData->time_limit ?? 0);
            if ($sec > 0) $timeLimitMs = $sec * 1000;
        }

        $isTimeout = ($timeLimitMs > 0 && $timeTakenMs > $timeLimitMs);
        $isCorrect = (!$isTimeout) && $this->validatePath($userAnswerJson, is_array($gridJson) ? $gridJson : []);

        $status = $isTimeout ? 'timeout' : ($isCorrect ? 'win' : 'fail');

        return [
            'correct'        => (bool)$isCorrect,
            'status'         => $status,
            'time_taken_ms'  => $timeTakenMs,
            'time_limit_ms'  => $timeLimitMs,
            'timeout'        => (bool)$isTimeout,
        ];
    }

    private function validatePath(array $userAnswer, array $gridJson): bool
    {
        $rawPath =
            $userAnswer['final_path']
            ?? ($userAnswer['last_validation']['path'] ?? null)
            ?? $userAnswer['path']
            ?? $userAnswer['route']
            ?? $userAnswer['steps']
            ?? null;

        if (is_string($rawPath)) {
            $decoded = json_decode($rawPath, true);
            if (is_array($decoded)) $rawPath = $decoded;
        }

        if (!is_array($rawPath) || empty($rawPath)) return false;

        // ✅ if last item is EARTH => WIN
        $last = end($rawPath);
        if ($last === 'EARTH') return true;

        // ✅ else end at last column => WIN
        $N = (int)($userAnswer['grid_dim'] ?? 3);
        $MINI = (int)($userAnswer['mini_dim'] ?? 3);
        $M = $N * $MINI;

        $lastNum = is_numeric($last) ? (int)$last : 0;
        if ($lastNum <= 0) return false;

        $col = ($lastNum - 1) % $M;
        return ($col === ($M - 1));
    }

    /* ============================================================
     | ✅ Folder Options (same like Door)
     *============================================================ */

    public function folderOptions(Request $request)
    {
        try {
            if (!Schema::hasTable('user_folders')) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $rows = DB::table('user_folders')
                ->when(Schema::hasColumn('user_folders', 'deleted_at'), fn($q) => $q->whereNull('deleted_at'))
                ->orderBy('title', 'asc')
                ->get(['id', 'title']);

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

    /* ============================================================
     | ✅ INDEX (Door-style filters + joins + pagination)
     | GET /api/path-game-results
     *============================================================ */

    public function index(Request $request)
    {
        Log::info('PathGameResult.index: start', [
            'ip' => $request->ip(),
            'query' => $request->query(),
        ]);

        try {
            $actor  = $this->actor($request);
            $role   = $this->normalizeRole($actor['role'] ?? '');
            $userId = (int)($actor['id'] ?? 0);

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
                $arr = is_array($v) ? $v : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);
                $out = [];
                foreach ($arr as $item) {
                    $item = $clean($item);
                    if ($item !== null) $out[] = (string)$item;
                }
                return array_values(array_unique($out));
            };

            $toIntList = function ($v) use ($clean) {
                if ($v === null) return [];
                $arr = is_array($v) ? $v : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);
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

            // ✅ safe columns
            $hasPublish = Schema::hasColumn('path_game_results', 'publish_to_student');

            $hasFolderTable = Schema::hasTable('user_folders');
            $hasFolderDeletedAt = $hasFolderTable ? Schema::hasColumn('user_folders', 'deleted_at') : false;

            $hasUserDeletedAt = Schema::hasColumn('users', 'deleted_at');

            // ✅ Base query
            $q = DB::table('path_game_results as pgr')
                ->join('path_games as pg', 'pgr.path_game_id', '=', 'pg.id')
                ->join('users as u', 'pgr.user_id', '=', 'u.id')
                ->whereNull('pgr.deleted_at')
                ->whereNull('pg.deleted_at');

            if ($hasUserDeletedAt) $q->whereNull('u.deleted_at');

            if ($hasFolderTable) {
                $q->leftJoin('user_folders as uf', function ($j) use ($hasFolderDeletedAt) {
                    $j->on('uf.id', '=', 'u.user_folder_id');
                    if ($hasFolderDeletedAt) $j->whereNull('uf.deleted_at');
                });
            }

            // ✅ percentage expression (Path score might exceed 100 due to bonus => clamp)
            $pctExpr = 'LEAST(COALESCE(pgr.score,0), 100.0)';

            $select = [
                'pgr.id as result_id',
                'pgr.uuid as result_uuid',
                'pgr.path_game_id',
                'pgr.user_id',
                'pgr.attempt_no',
                'pgr.score',
                'pgr.time_taken_ms',
                'pgr.status as attempt_status',
                'pgr.created_at as result_created_at',

                'pg.id as game_id',
                'pg.uuid as game_uuid',
                'pg.title as game_title',
                'pg.status as game_status',

                'u.id as student_id',
                'u.uuid as student_uuid',
                'u.name as student_name',
                'u.email as student_email',
                'u.user_folder_id as user_folder_id',

                DB::raw("ROUND($pctExpr, 2) as percentage_pct"),
            ];

            if ($hasPublish) $select[] = 'pgr.publish_to_student';
            else $select[] = DB::raw('0 as publish_to_student');

            if ($hasFolderTable) {
                $select[] = 'uf.id as folder_id';
                $select[] = 'uf.title as folder_title';
            } else {
                $select[] = DB::raw('NULL as folder_id');
                $select[] = DB::raw('NULL as folder_title');
            }

            $q->select($select);

            // ✅ Student visibility rule
            if ($role === 'student') {
                $q->where('pgr.user_id', $userId);
                if ($hasPublish) $q->where('pgr.publish_to_student', 1);
            }

            /* =======================
             | Filters (Multi + Mixed)
             *======================= */

            // path_game_id supports ids + uuids => ?path_game_id=1,uuid1
            $gameKeys = $toStrList($request->query('path_game_id'));
            if (!empty($gameKeys)) {
                $ids = [];
                $uuids = [];
                foreach ($gameKeys as $v) {
                    if (is_numeric($v)) $ids[] = (int)$v;
                    else $uuids[] = (string)$v;
                }
                $ids = array_values(array_unique($ids));
                $uuids = array_values(array_unique($uuids));

                $q->where(function ($w) use ($ids, $uuids) {
                    if (!empty($ids)) $w->orWhereIn('pg.id', $ids);
                    if (!empty($uuids)) $w->orWhereIn('pg.uuid', $uuids);
                });
            }

            // game_uuid multi
            $gameUuids = $toStrList($request->query('game_uuid'));
            if (!empty($gameUuids)) $q->whereIn('pg.uuid', $gameUuids);

            // student_email multi OR
            $emailTerms = $toStrList($request->query('student_email'));
            if (!empty($emailTerms)) {
                $q->where(function ($w) use ($emailTerms) {
                    foreach ($emailTerms as $t) $w->orWhere('u.email', 'like', "%{$t}%");
                });
            }

            // folder id
            $folderIds = $toIntList($request->query('user_folder_id'));
            if (!empty($folderIds)) $q->whereIn('u.user_folder_id', $folderIds);

            // folder title
            $folderTitles = $toStrList($request->query('folder_title'));
            if (!empty($folderTitles) && $hasFolderTable) {
                $q->where(function ($w) use ($folderTitles) {
                    foreach ($folderTitles as $t) $w->orWhere('uf.title', 'like', "%{$t}%");
                });
            }

            // search q/search
            $txt = $clean($request->query('q', null));
            $alt = $clean($request->query('search', null));
            $search = $txt ?? $alt;

            if ($search !== null) {
                $q->where(function ($w) use ($search) {
                    $w->where('u.name', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('pg.title', 'like', "%{$search}%")
                      ->orWhere('pgr.uuid', 'like', "%{$search}%");
                });
            }

            // attempt_status multi
            $attemptStatus = $toStrList($request->query('attempt_status'));
            if (!empty($attemptStatus)) $q->whereIn('pgr.status', $attemptStatus);

            // attempt_no multi
            $attemptNos = $toIntList($request->query('attempt_no'));
            if (!empty($attemptNos)) $q->whereIn('pgr.attempt_no', $attemptNos);

            // publish_to_student filter (non-student)
            if ($hasPublish && $role !== 'student') {
                $pub = $toBool01($request->query('publish_to_student'));
                if ($pub !== null) $q->where('pgr.publish_to_student', $pub);
            }

            // date range
            $from = $clean($request->query('from'));
            $to   = $clean($request->query('to'));

            if ($from !== null || $to !== null) {
                try {
                    $start = $from ? Carbon::parse($from)->startOfDay() : null;
                    $end   = $to ? Carbon::parse($to)->endOfDay() : null;

                    if ($start && $end) {
                        if ($start->gt($end)) { $tmp = $start; $start = $end; $end = $tmp; }
                        $q->whereBetween('pgr.created_at', [$start, $end]);
                    } elseif ($start) {
                        $q->where('pgr.created_at', '>=', $start);
                    } elseif ($end) {
                        $q->where('pgr.created_at', '<=', $end);
                    }
                } catch (\Throwable $e) {}
            }

            // min/max percentage
            $minPct = $toFloat($request->query('min_percentage'));
            $maxPct = $toFloat($request->query('max_percentage'));

            if ($minPct !== null && $maxPct !== null) {
                if ($minPct > $maxPct) { $tmp = $minPct; $minPct = $maxPct; $maxPct = $tmp; }
                $q->whereRaw("$pctExpr BETWEEN ? AND ?", [$minPct, $maxPct]);
            } else {
                if ($minPct !== null) $q->whereRaw("$pctExpr >= ?", [$minPct]);
                if ($maxPct !== null) $q->whereRaw("$pctExpr <= ?", [$maxPct]);
            }

            /* =======================
             | Sorting
             *======================= */
            $sort = (string)$request->query('sort', '-result_created_at');
            $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $key  = ltrim($sort, '-');

            $sortMap = [
                'student_name'       => 'u.name',
                'student_email'      => 'u.email',
                'game_title'         => 'pg.title',
                'score'              => 'pgr.score',
                'percentage'         => DB::raw($pctExpr),
                'attempt_no'         => 'pgr.attempt_no',
                'result_created_at'  => 'pgr.created_at',
                'publish_to_student' => $hasPublish ? 'pgr.publish_to_student' : 'pgr.created_at',
                'folder_title'       => $hasFolderTable ? 'uf.title' : 'u.name',
            ];

            $orderCol = $sortMap[$key] ?? 'pgr.created_at';

            $q->orderBy($orderCol, $dir)->orderBy('pgr.id', 'desc');

            /* =======================
             | No pagination support
             *======================= */
            if ($request->boolean('paginate') === false || $request->query('paginate') === 'false') {
                $rows = $q->get();

                $items = collect($rows)->map(function ($r) use ($hasPublish) {
                    return [
                        'student' => [
                            'id' => (int)($r->student_id ?? 0),
                            'uuid' => (string)($r->student_uuid ?? ''),
                            'name' => (string)($r->student_name ?? ''),
                            'email' => (string)($r->student_email ?? ''),
                            'user_folder_id' => $r->user_folder_id ?? null,
                            'folder_id' => $r->folder_id ?? null,
                            'folder_title' => $r->folder_title ?? null,
                            'folder_name' => $r->folder_title ?? null,
                        ],
                        'game' => [
                            'id' => (int)($r->game_id ?? 0),
                            'uuid' => (string)($r->game_uuid ?? ''),
                            'title' => (string)($r->game_title ?? ''),
                            'status' => (string)($r->game_status ?? ''),
                        ],
                        'attempt' => [
                            'status' => (string)($r->attempt_status ?? ''),
                        ],
                        'result' => [
                            'id' => (int)($r->result_id ?? 0),
                            'uuid' => (string)($r->result_uuid ?? ''),
                            'attempt_no' => (int)($r->attempt_no ?? 0),
                            'score' => (float)($r->score ?? 0),
                            'percentage' => (float)($r->percentage_pct ?? 0),
                            'publish_to_student' => $hasPublish ? (int)($r->publish_to_student ?? 0) : 0,
                            'time_taken_ms' => (int)($r->time_taken_ms ?? 0),
                            'created_at' => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
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

            /* =======================
             | Pagination
             *======================= */
            $perPage = max(1, min(100, (int)$request->query('per_page', 20)));
            $page    = max(1, (int)$request->query('page', 1));

            $base  = clone $q;
            $total = (clone $base)->distinct()->count('pgr.id');

            $rows = (clone $base)->forPage($page, $perPage)->get();

            $items = collect($rows)->map(function ($r) use ($hasPublish) {
                return [
                    'student' => [
                        'id' => (int)($r->student_id ?? 0),
                        'uuid' => (string)($r->student_uuid ?? ''),
                        'name' => (string)($r->student_name ?? ''),
                        'email' => (string)($r->student_email ?? ''),
                        'user_folder_id' => $r->user_folder_id ?? null,
                        'folder_id' => $r->folder_id ?? null,
                        'folder_title' => $r->folder_title ?? null,
                        'folder_name' => $r->folder_title ?? null,
                    ],
                    'game' => [
                        'id' => (int)($r->game_id ?? 0),
                        'uuid' => (string)($r->game_uuid ?? ''),
                        'title' => (string)($r->game_title ?? ''),
                        'status' => (string)($r->game_status ?? ''),
                    ],
                    'attempt' => [
                        'status' => (string)($r->attempt_status ?? ''),
                    ],
                    'result' => [
                        'id' => (int)($r->result_id ?? 0),
                        'uuid' => (string)($r->result_uuid ?? ''),
                        'attempt_no' => (int)($r->attempt_no ?? 0),
                        'score' => (float)($r->score ?? 0),
                        'percentage' => (float)($r->percentage_pct ?? 0),
                        'publish_to_student' => $hasPublish ? (int)($r->publish_to_student ?? 0) : 0,
                        'time_taken_ms' => (int)($r->time_taken_ms ?? 0),
                        'created_at' => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
                        'result_created_at' => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
                    ],
                ];
            })->values();

            $lastPage = (int)ceil($total / max($perPage, 1));

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'total' => (int)$total,
                    'per_page' => (int)$perPage,
                    'page' => (int)$page,
                    'total_pages' => (int)$lastPage,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('PathGameResult.index: exception', [
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

    /* ============================================================
     | ✅ SHOW (ID/UUID)
     | GET /api/path-game-results/{idOrUuid}
     *============================================================ */

    public function show(string $idOrUuid)
    {
        try {
            $result = DB::table('path_game_results as pgr')
                ->join('path_games as pg', 'pgr.path_game_id', '=', 'pg.id')
                ->join('users as u', 'pgr.user_id', '=', 'u.id')
                ->whereNull('pgr.deleted_at')
                ->whereNull('pg.deleted_at')
                ->where(function ($q) use ($idOrUuid) {
                    $q->where('pgr.id', $idOrUuid)->orWhere('pgr.uuid', $idOrUuid);
                })
                ->select([
                    'pgr.*',
                    'pg.uuid as game_uuid',
                    'pg.title as game_title',
                    'u.name as student_name',
                    'u.email as student_email',
                ])
                ->first();

            if (!$result) {
                return response()->json(['success' => false, 'message' => 'Result not found'], 404);
            }

            $result = $this->mapResultRow($result);

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error while fetching result',
            ], 500);
        }
    }

    /* ============================================================
     | ✅ STORE (kept as-is, useful for admin inserts)
     | POST /api/path-game-results
     *============================================================ */

    public function store(Request $request)
    {
        $payload = $request->all();
        $this->normalizeUserAnswerJson($payload);

        $validator = Validator::make($payload, [
            'path_game_id' => ['required', 'integer', 'exists:path_games,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'attempt_no' => ['required', 'integer', 'min:1'],
            'user_answer_json' => ['nullable', 'array'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'time_taken_ms' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:win,fail,timeout,in_progress'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $now = Carbon::now();
            $ip = $this->getClientIp($request);
            $uuid = (string)Str::uuid();

            $insertId = DB::table('path_game_results')->insertGetId([
                'uuid' => $uuid,
                'path_game_id' => (int)$payload['path_game_id'],
                'user_id' => (int)$payload['user_id'],
                'attempt_no' => (int)$payload['attempt_no'],
                'user_answer_json' => isset($payload['user_answer_json']) ? json_encode($payload['user_answer_json']) : null,
                'score' => (float)($payload['score'] ?? 0),
                'time_taken_ms' => isset($payload['time_taken_ms']) ? (int)$payload['time_taken_ms'] : null,
                'status' => $payload['status'],
                'created_at' => $now,
                'updated_at' => $now,
                'created_at_ip' => $ip,
                'updated_at_ip' => $ip,
                'deleted_at' => null,
            ]);

            DB::commit();

            $result = DB::table('path_game_results')->where('id', $insertId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Result created successfully.',
                'data' => $this->mapResultRow($result),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create result.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* ============================================================
     | ✅ SUBMIT (Door-style: strict attempts + evaluation + insert)
     | POST /api/path-games/{gameKey}/submit
     *============================================================ */

   public function submit(Request $request, string $gameKey)
{
    Log::info('PathGame.submit: start', [
        'ip' => $request->ip(),
        'game_key' => $gameKey,
        'payload_keys' => array_keys($request->all()),
    ]);

    $actor  = $this->actor($request);
    $userId = (int)($actor['id'] ?? 0);

    if ($userId <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Authentication required.',
        ], 401);
    }

    $game = $this->findGame($gameKey);
    if (!$game) {
        return response()->json([
            'success' => false,
            'message' => 'Game not found.',
        ], 404);
    }

    if (($game->status ?? '') !== 'active') {
        return response()->json([
            'success' => false,
            'message' => 'This game is not active.',
        ], 403);
    }

    $payload = $request->all();

    // ✅ Ensure user_answer_json is always array
    if (empty($payload['user_answer_json']) || !is_array($payload['user_answer_json'])) {
        $payload['user_answer_json'] = [];
    }

    /**
     * ✅ Auto-pick time_taken_ms if frontend didn’t send explicitly.
     * Works for Replay v2 payload.
     */
    $timeFromJson =
        (int) data_get($payload, 'user_answer_json.attempt.time_taken_ms', 0)
        ?: (int) data_get($payload, 'user_answer_json.timing.total_ms', 0)
        ?: (int) data_get($payload, 'user_answer_json.timing.total_ms', 0);

    if (!isset($payload['time_taken_ms']) || !is_numeric($payload['time_taken_ms'])) {
        $payload['time_taken_ms'] = $timeFromJson;
    }

    /**
     * ✅ Normalize Replay v2 into keys your evaluation understands:
     * - final_path
     * - rotation_log
     * - events_map
     * - meta.start_index/end_index/reached_earth
     */
    $this->normalizeUserAnswerJson($payload, $game);

    $validator = Validator::make($payload, [
        'user_answer_json' => ['required', 'array'],
        'time_taken_ms'    => ['required', 'integer', 'min:0'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    try {
        return DB::transaction(function () use ($request, $userId, $game, $payload, $gameKey) {

            $maxAttempts = (int)($game->max_attempts ?? 1);
            if ($maxAttempts < 1) $maxAttempts = 1;

            // ✅ Strict count with lock
            $attemptsUsed = (int)DB::table('path_game_results')
                ->where('path_game_id', (int)$game->id)
                ->where('user_id', (int)$userId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->count();

            $attemptNo = $attemptsUsed + 1;

            if ($attemptNo > $maxAttempts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum attempts exceeded.',
                    'data' => [
                        'max_attempts' => $maxAttempts,
                        'attempts_used' => $attemptsUsed,
                    ],
                ], 403);
            }

            $timeTakenMs = (int)$payload['time_taken_ms'];

            // ✅ Evaluate once (your existing logic)
            $evaluation = $this->evaluateAnswer(
                (array)$payload['user_answer_json'],
                $game,
                $timeTakenMs
            );

            $isCorrect   = (bool)($evaluation['correct'] ?? false);
            $finalStatus = (string)($evaluation['status'] ?? 'fail');
            $limitMs     = (int)($evaluation['time_limit_ms'] ?? ((int)($game->time_limit_sec ?? 0) * 1000));
            $isTimeout   = (bool)($evaluation['timeout'] ?? false);

            // ✅ Score (unchanged)
            $score = ($finalStatus === 'win' && !$isTimeout) ? 1 : 0;

            // ✅ Enrich JSON for storage (Replay v2 + Summary)
            $enrichedUserAnswerJson = $this->enrichPathUserAnswerJsonForStorage(
                (array)$payload['user_answer_json'],
                $game,
                $timeTakenMs,
                $limitMs,
                $finalStatus,
                $isCorrect,
                (int)$score
            );

            $uuid = (string)Str::uuid();
            $now  = now();
            $ip   = $this->getClientIp($request);

            $resultId = DB::table('path_game_results')->insertGetId([
                'uuid'             => $uuid,
                'path_game_id'     => (int)$game->id,
                'user_id'          => (int)$userId,
                'attempt_no'       => (int)$attemptNo,

                // ✅ store enriched replay-ready JSON
                'user_answer_json' => json_encode($enrichedUserAnswerJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

                'score'            => (float)$score,
                'time_taken_ms'    => (int)$timeTakenMs,
                'status'           => (string)$finalStatus,
                'created_at'       => $now,
                'updated_at'       => $now,
                'created_at_ip'    => $ip,
                'updated_at_ip'    => $ip,
                'deleted_at'       => null,
            ]);

            $msg = 'Your answer is incorrect. Please try again.';
            if ($finalStatus === 'win') $msg = 'Congratulations! You reached Earth with a correct path.';
            if ($finalStatus === 'timeout') $msg = 'Time is up! You reached late. Please try again.';

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => [
                    'id' => (int)$resultId,
                    'uuid' => (string)$uuid,
                    'attempt_no' => (int)$attemptNo,
                    'score' => (int)$score,
                    'status' => (string)$finalStatus,
                    'time_taken_ms' => (int)$timeTakenMs,
                    'max_attempts' => (int)$maxAttempts,
                    'remaining_attempts' => max(0, $maxAttempts - $attemptNo),
                    'evaluation' => [
                        'correct' => $isCorrect,
                        'timeout' => $isTimeout,
                        'time_limit_ms' => $limitMs,
                    ],
                ],
            ], 201);
        });

    } catch (\Throwable $e) {
        Log::error('PathGame.submit: exception', [
            'game_key' => $gameKey,
            'user_id' => $userId,
            'message' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to submit answer.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

private function enrichPathUserAnswerJsonForStorage(
    array $ua,
    $game,
    int $timeTakenMs,
    int $timeLimitMs,
    string $finalStatus,
    bool $isCorrect,
    int $score
): array {

    $timeline = data_get($ua, 'replay.timeline', []);
    if (!is_array($timeline)) $timeline = [];

    usort($timeline, fn($a, $b) => (int)($a['t_ms'] ?? 0) <=> (int)($b['t_ms'] ?? 0));

    // ✅ Path extraction (prefer normalized meta.path_numeric)
    $pathNumeric = (array) data_get($ua, 'meta.path_numeric', []);
    $pathNumeric = array_values(array_filter($pathNumeric, fn($x) => is_numeric($x)));

    if (empty($pathNumeric)) {
        // fallback build from timeline moves
        $current = (int) data_get($ua, 'replay.start_index', 0);
        if ($current > 0) $pathNumeric[] = $current;

        foreach ($timeline as $act) {
            if (($act['type'] ?? '') === 'move') {
                $p = (array)($act['payload'] ?? []);
                if ((bool)($p['allowed'] ?? true)) {
                    $to = (int)($p['to'] ?? 0);
                    if ($to > 0) $pathNumeric[] = $to;
                }
            }
        }
    }

    $startIndex = $pathNumeric[0] ?? (int) data_get($ua, 'replay.start_index', 0);
    $endIndex   = !empty($pathNumeric) ? $pathNumeric[count($pathNumeric) - 1] : null;

    $targetIndex = (int) data_get($ua, 'replay.target_index', 0);
    $reachedEarth = (bool) data_get($ua, 'meta.reached_earth', false)
        || ($targetIndex > 0 && $endIndex === $targetIndex);

    // ✅ Rotation analysis from timeline rotate actions
    $rotationActions = [];
    foreach ($timeline as $act) {
        if (($act['type'] ?? '') === 'rotate') {
            $p = (array)($act['payload'] ?? []);
            $rotationActions[] = [
                'rotation_step' => (int)($p['rotation_step'] ?? 0),
                'tile_index'    => (int)($p['tile_index'] ?? 0),
                'dir'           => (string)($p['dir'] ?? ''),
                'rotate_by_deg' => (int)($p['rotate_by_deg'] ?? 90),
                'before_deg'    => (int)($p['before_deg'] ?? 0),
                'after_deg'     => (int)($p['after_deg'] ?? 0),
                't_ms'          => (int)($act['t_ms'] ?? 0),
            ];
        }
    }

    $gridWasRotated = !empty($rotationActions);

    // ✅ time spent per index (based on move timeline)
    $timePerIndex = [];
    $moveDeltas   = [];

    $prevMoveT = 0;
    $prevIndex = $startIndex;

    foreach ($timeline as $act) {
        if (($act['type'] ?? '') !== 'move') continue;

        $tms = (int)($act['t_ms'] ?? 0);
        $p   = (array)($act['payload'] ?? []);

        $allowed = (bool)($p['allowed'] ?? true);
        if (!$allowed) continue;

        $from = (int)($p['from'] ?? 0);
        $to   = (int)($p['to'] ?? 0);

        $delta = max(0, $tms - $prevMoveT);
        $prevMoveT = $tms;

        // spend time on "from" index
        if ($from > 0) {
            $timePerIndex[$from] = ($timePerIndex[$from] ?? 0) + $delta;
        }

        $moveDeltas[] = [
            'from' => $from,
            'to'   => $to,
            't_ms' => $tms,
            'delta_ms' => $delta,
        ];

        $prevIndex = $to;
    }

    // ✅ rotation delta timeline (time between rotations)
    $rotationDeltas = [];
    $prevRotT = 0;

    foreach ($rotationActions as $i => $r) {
        $tms = (int)($r['t_ms'] ?? 0);
        $delta = max(0, $tms - $prevRotT);
        $prevRotT = $tms;

        $rotationDeltas[] = [
            'i'         => $i + 1,
            'tile_index'=> (int)($r['tile_index'] ?? 0),
            'dir'       => (string)($r['dir'] ?? ''),
            't_ms'      => $tms,
            'delta_ms'  => $delta
        ];
    }

    // ✅ enforce game block
    $ua['version'] = $ua['version'] ?? '2.0';

    $ua['game'] = array_merge((array)($ua['game'] ?? []), [
        'grid_dim'          => (int) data_get($ua, 'game.grid_dim', (int)($game->grid_dim ?? 0)),
        'grid_total_tiles'  => (int) data_get($ua, 'game.grid_total_tiles', ((int)($game->grid_dim ?? 0) * (int)($game->grid_dim ?? 0))),
        'time_limit_sec'    => (int) data_get($ua, 'game.time_limit_sec', (int)($game->time_limit_sec ?? 0)),
        'max_attempts'      => (int) data_get($ua, 'game.max_attempts', (int)($game->max_attempts ?? 1)),
        'rotation_enabled'  => (bool) data_get($ua, 'game.rotation_enabled', (bool)($game->rotation_enabled ?? true)),
        'rotation_mode'     => (string) data_get($ua, 'game.rotation_mode', (string)($game->rotation_mode ?? 'both')),
    ]);

    // ✅ attempt block
    $ua['attempt'] = array_merge((array)($ua['attempt'] ?? []), [
        'status'        => $finalStatus,
        'score'         => $score,
        'time_taken_ms' => $timeTakenMs,
    ]);

    // ✅ replay grid_rotation enrichment
    if (empty($ua['replay']) || !is_array($ua['replay'])) $ua['replay'] = [];

    if (empty($ua['replay']['grid_rotation']) || !is_array($ua['replay']['grid_rotation'])) {
        $ua['replay']['grid_rotation'] = [];
    }

    $ua['replay']['grid_rotation'] = array_merge((array)$ua['replay']['grid_rotation'], [
        'grid_was_rotated' => $gridWasRotated
    ]);

    // ✅ replay summary
    $ua['replay_summary'] = [
        'start_index'      => $startIndex,
        'end_index'        => $endIndex,
        'target_index'     => $targetIndex,
        'reached_earth'    => $reachedEarth,
        'path_numeric'     => $pathNumeric,
        'moves_count'      => count(array_filter($timeline, fn($x) => ($x['type'] ?? '') === 'move')),
        'rotations_count'  => count($rotationActions),
        'events_count'     => count((array) data_get($ua, 'events_map', [])),
        'correct_path'     => $isCorrect,
        'status'           => $finalStatus,
    ];

    // ✅ meta (top-level safe)
    $ua['meta'] = array_merge((array)($ua['meta'] ?? []), [
        'start_index'    => $startIndex,
        'end_index'      => $endIndex,
        'reached_earth'  => $reachedEarth,
        'path_numeric'   => $pathNumeric,
        'time_taken_ms'  => $timeTakenMs,
        'time_limit_ms'  => $timeLimitMs,
        'status'         => $finalStatus,
        'correct_path'   => $isCorrect,
        'score'          => $score,
        'saved_at'       => now()->toDateTimeString(),
    ]);

    // ✅ timing block
    $ua['timing'] = array_merge((array)($ua['timing'] ?? []), [
        'total_ms'                 => $timeTakenMs,
        'time_spent_per_index_ms'  => $timePerIndex,
        'move_deltas'              => $moveDeltas,
        'rotation_deltas'          => $rotationDeltas,
    ]);

    return $ua;
}

    /* ============================================================
     | ✅ Result Detail (Student)
     | GET /api/path-game/result/{resultKey}
     *============================================================ */
public function resultDetail(Request $request, string $resultKey)
{
    $trace = (string) \Illuminate\Support\Str::uuid();
    $t0 = microtime(true);

    \Log::info("[PathGame.resultDetail][$trace] START", [
        'ip' => $request->ip(),
        'result_key' => $resultKey,
        'url' => $request->fullUrl(),
        'ua' => $request->userAgent(),
    ]);

    $actor  = $this->actor($request);
    $role   = $this->normalizeRole($actor['role'] ?? '');
    $userId = (int)($actor['id'] ?? 0);

    \Log::debug("[PathGame.resultDetail][$trace] ACTOR", [
        'role' => $role,
        'user_id' => $userId,
        'actor' => $actor,
    ]);

    if ($userId <= 0) {
        \Log::warning("[PathGame.resultDetail][$trace] UNAUTHORIZED", [
            'role' => $role,
            'user_id' => $userId,
        ]);
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $hasPublish = Schema::hasColumn('path_game_results', 'publish_to_student');

    $q = DB::table('path_game_results as r')
        ->join('path_games as g', 'g.id', '=', 'r.path_game_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')
        ->whereNull('r.deleted_at')
        ->whereNull('g.deleted_at')
        ->select(array_filter([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.path_game_id',
            'r.user_id',
            'r.attempt_no',
            'r.score',
            'r.time_taken_ms',
            'r.status',
            'r.user_answer_json',
            'r.created_at as result_created_at',

            $hasPublish ? 'r.publish_to_student as publish_to_student' : null,

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
        ]));

    $this->applyIdOrUuidWhere($q, 'r.id', 'r.uuid', $resultKey);
    $row = $q->first();

    if (!$row) {
        \Log::warning("[PathGame.resultDetail][$trace] NOT_FOUND", [
            'result_key' => $resultKey,
        ]);
        return response()->json(['success' => false, 'message' => 'Result not found'], 404);
    }

    // ✅ student can only view own result
    if ($role === 'student' && (int)$row->user_id !== $userId) {
        \Log::warning("[PathGame.resultDetail][$trace] FORBIDDEN_OTHER_USER", [
            'result_id' => $row->result_id,
            'row_user_id' => (int)$row->user_id,
            'auth_user_id' => $userId,
        ]);
        return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
    }

    // ✅ if student, allow only published results
    if ($role === 'student' && $hasPublish) {
        $pub = (int)($row->publish_to_student ?? 0);
        if ($pub !== 1) {
            \Log::info("[PathGame.resultDetail][$trace] NOT_PUBLISHED", [
                'result_id' => (int)$row->result_id,
                'publish_to_student' => $pub,
            ]);
            return response()->json(['success' => false, 'message' => 'Not published'], 403);
        }
    }

    $grid   = $this->jsonSafe($row->grid_json, []);
    $answer = $this->jsonSafe($row->user_answer_json, []);

    // ✅ Snapshot returned to frontend (replay friendly)
    $snapshot = is_array($answer) ? $answer : [];

    // ============================================================
    // ✅ Prefer Replay v2 data (timeline + grid_rotation + snapshots)
    // ============================================================
    $timeline = data_get($snapshot, 'replay.timeline', []);
    $hasV2Replay = is_array($timeline) && count($timeline) > 0;

    $gridDim = (int)($row->grid_dim ?? 3);
    $totalTiles = $gridDim * $gridDim;

    $pathNumeric = [];
    $rotations = [];
    $eventsMap = [];

    $startIndex = null;
    $endIndex   = null;
    $targetIndex = null;

    if ($hasV2Replay) {

        // ✅ Sort timeline safe
        usort($timeline, fn($a, $b) => (int)($a['t_ms'] ?? 0) <=> (int)($b['t_ms'] ?? 0));

        $startIndex = (int) data_get($snapshot, 'replay.start_index', 0);
        $targetIndex = (int) data_get($snapshot, 'replay.target_index', 0);

        $currentIndex = $startIndex > 0 ? $startIndex : (int) data_get($snapshot, 'replay.timeline.0.payload.current_index', 0);
        if ($currentIndex > 0) $pathNumeric[] = $currentIndex;

        $moveStep = 0;
        $rotationStepSeen = 0;

        foreach ($timeline as $act) {
            $type = (string)($act['type'] ?? '');
            $tms  = (int)($act['t_ms'] ?? 0);
            $p    = (array)($act['payload'] ?? []);

            if ($type === 'move') {
                $moveStep++;

                $allowed = (bool)($p['allowed'] ?? true);
                if ($allowed) {
                    $to = (int)($p['to'] ?? 0);
                    if ($to > 0) {
                        $pathNumeric[] = $to;
                        $currentIndex = $to;
                    }
                }
            }

            if ($type === 'rotate') {
                $rotationStepSeen++;

                $rotations[] = [
                    'rotation_step' => (int)($p['rotation_step'] ?? $rotationStepSeen),
                    'at_move_step'  => $moveStep, // ✅ rotation happened after this move count
                    'tile_index'    => (int)($p['tile_index'] ?? 0),
                    'dir'           => (string)($p['dir'] ?? 'cw'),
                    'rotate_by_deg' => (int)($p['rotate_by_deg'] ?? 90),
                    'before_deg'    => (int)($p['before_deg'] ?? 0),
                    'after_deg'     => (int)($p['after_deg'] ?? 0),
                    't_ms'          => $tms
                ];
            }

            if ($type === 'event') {
                $name = (string)($p['name'] ?? '');
                if ($name !== '') {
                    $eventsMap[$name] = [
                        'at_index' => (int)($p['at_index'] ?? 0),
                        't_ms'     => $tms,
                        'meta'     => (array)($p['meta'] ?? []),
                    ];
                }
            }
        }

        $endIndex = !empty($pathNumeric) ? $pathNumeric[count($pathNumeric) - 1] : null;

        // ✅ Ensure grid_rotation exists for frontend replay
        $gridRotation = (array) data_get($snapshot, 'replay.grid_rotation', []);

        $initialDeg = (array) data_get($gridRotation, 'initial_deg_by_index', []);
        $finalDeg   = (array) data_get($gridRotation, 'final_deg_by_index', []);

        // ✅ If missing, compute best-effort final from rotations
        if (empty($initialDeg)) {
            $initialDeg = [];
            for ($i = 1; $i <= $totalTiles; $i++) $initialDeg[(string)$i] = 0;
        }

        if (empty($finalDeg)) {
            $finalDeg = $initialDeg;

            foreach ($rotations as $r) {
                $tile = (int)($r['tile_index'] ?? 0);
                if ($tile <= 0) continue;

                $before = (int)($finalDeg[(string)$tile] ?? 0);
                $dir    = strtolower((string)($r['dir'] ?? 'cw'));
                $by     = (int)($r['rotate_by_deg'] ?? 90);

                $after = $before;
                if ($dir === 'ccw') $after = ($before - $by) % 360;
                else $after = ($before + $by) % 360;

                if ($after < 0) $after += 360;
                $finalDeg[(string)$tile] = $after;
            }
        }

        $snapshot['replay'] = array_merge((array)($snapshot['replay'] ?? []), [
            'timeline' => $timeline,
            'grid_rotation' => array_merge((array)($snapshot['replay']['grid_rotation'] ?? []), [
                'grid_was_rotated' => count($rotations) > 0,
                'initial_deg_by_index' => $initialDeg,
                'final_deg_by_index' => $finalDeg,
            ]),
            'state_snapshots' => (array) data_get($snapshot, 'replay.state_snapshots', []),
        ]);

    } else {

        // ============================================================
        // ✅ Backward Compatible: old stored JSON (rotation_log/final_path)
        // ============================================================
        $pathRaw = [];

        if (!empty($snapshot['meta']['path_raw']) && is_array($snapshot['meta']['path_raw'])) {
            $pathRaw = $snapshot['meta']['path_raw'];
        } elseif (!empty($snapshot['last_validation']['path']) && is_array($snapshot['last_validation']['path'])) {
            $pathRaw = $snapshot['last_validation']['path'];
        } elseif (!empty($snapshot['final_path']) && is_array($snapshot['final_path'])) {
            $pathRaw = $snapshot['final_path'];
        }

        $pathNumeric = array_values(array_filter($pathRaw, fn($x) => is_numeric($x)));

        $startIndex = $snapshot['meta']['start_index'] ?? ($pathNumeric[0] ?? null);
        $endIndex   = $snapshot['meta']['end_index']   ?? (!empty($pathNumeric) ? $pathNumeric[count($pathNumeric) - 1] : null);

        // ✅ Convert old rotation_log into rotations array (best-effort)
        $rotationLog = (!empty($snapshot['rotation_log']) && is_array($snapshot['rotation_log']))
            ? $snapshot['rotation_log']
            : [];

        usort($rotationLog, fn($a, $b) => (int)($a['t_ms'] ?? 0) <=> (int)($b['t_ms'] ?? 0));

        $prevCountByTile = [];
        foreach ($rotationLog as $idx => $r) {
            $tile = isset($r['tile_index']) ? (int)$r['tile_index'] : null;
            if (!$tile) continue;

            $type  = strtolower((string)($r['rotation_type'] ?? 'cw')); // cw/ccw
            $count = (int)($r['rotation_count'] ?? 1);
            $tms   = (int)($r['t_ms'] ?? 0);

            $prevCount = $prevCountByTile[$tile] ?? 0;
            $deltaCount = max(1, $count - $prevCount);
            $prevCountByTile[$tile] = $count;

            $sign = ($type === 'ccw' || $type === 'anti' || $type === 'left') ? -1 : 1;
            $deltaDeg = $sign * 90 * $deltaCount;

            $rotations[] = [
                'rotation_step' => $idx + 1,
                'at_move_step'  => null,
                'tile_index'    => $tile,
                'dir'           => ($sign < 0 ? 'ccw' : 'cw'),
                'rotate_by_deg' => abs($deltaDeg),
                'before_deg'    => null,
                'after_deg'     => null,
                't_ms'          => $tms,
            ];
        }

        // ✅ create minimal replay object so frontend can still replay something
        $snapshot['replay'] = [
            'start_index' => (int)($startIndex ?? 0),
            'target_index' => null,
            'grid_rotation' => [
                'grid_was_rotated' => count($rotations) > 0,
            ],
            'timeline' => [],
            'state_snapshots' => [],
        ];
    }

    // ✅ Always attach unified frontend keys
    $snapshot['path'] = $pathNumeric;
    $snapshot['start_index'] = $startIndex;
    $snapshot['end_index'] = $endIndex;
    $snapshot['target_index'] = $targetIndex;

    // ✅ for rotation animations (legacy)
    $snapshot['rotations'] = $rotations;
    $snapshot['moves'] = $rotations;    // ✅ keep old frontend safe
    $snapshot['actions'] = $rotations;  // ✅ keep old frontend safe

    // ✅ attach events map if v2
    if (!empty($eventsMap)) {
        $snapshot['events_map'] = $eventsMap;
    }

    \Log::info("[PathGame.resultDetail][$trace] OK", [
        'time_ms' => (int) round((microtime(true) - $t0) * 1000),
        'result_id' => (int)$row->result_id,
        'user_id' => (int)$row->user_id,
        'role' => $role,
        'published' => $hasPublish ? (int)($row->publish_to_student ?? 0) : null,
        'has_v2_replay' => $hasV2Replay,
        'rotations_count' => count($rotations),
        'path_len' => count($pathNumeric),
        'start_index' => $startIndex,
        'end_index' => $endIndex,
    ]);

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
            'score' => (float)($row->score ?? 0),
            'time_taken_ms' => (int)($row->time_taken_ms ?? 0),
            'status' => (string)($row->status ?? ''),
            'publish_to_student' => $hasPublish ? (int)($row->publish_to_student ?? 0) : null,
            'result_created_at' => $row->result_created_at
                ? Carbon::parse($row->result_created_at)->toDateTimeString()
                : null,

            // ✅ original stored JSON
            'user_answer' => $answer,

            // ✅ frontend replay snapshot
            'snapshot' => $snapshot,
        ],
        'student' => [
            'id' => (int)$row->user_id,
            'uuid' => (string)($row->student_uuid ?? ''),
            'name' => (string)($row->student_name ?? ''),
            'email' => (string)($row->student_email ?? ''),
        ],
    ], 200);
}


    /* ============================================================
     | ✅ Result Detail (Instructor/Examiner)
     | GET /api/path-game/result/instructor/{resultKey}
     *============================================================ */

    public function resultDetailForInstructor(Request $request, string $resultKey)
    {
        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $q = DB::table('path_game_results as r')
            ->join('path_games as g', 'g.id', '=', 'r.path_game_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereNull('r.deleted_at')
            ->whereNull('g.deleted_at')
            ->select([
                'r.id as result_id',
                'r.uuid as result_uuid',
                'r.path_game_id',
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
            return response()->json(['success' => false, 'message' => 'Result not found'], 404);
        }

        // ✅ instructor/examiner restriction if assignment module exists
        if (in_array($role, ['instructor', 'examiner'], true)) {
            if (!$this->userAssignedToPathGame($userId, (int)$row->game_id)) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this path game'], 403);
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
                'score' => (float)($row->score ?? 0),
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

    /* ============================================================
     | ✅ Assigned Results For Game (Door-style)
     | GET /api/path-game/{gameKey}/assigned-results
     *============================================================ */

    public function assignedResultsForGame(Request $request, string $gameKey)
    {
        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $game = $this->findGame($gameKey);
        if (!$game) {
            return response()->json(['success' => false, 'message' => 'Path game not found'], 404);
        }

        // ✅ instructor/examiner restriction
        if (in_array($role, ['instructor', 'examiner'], true)) {
            if (!$this->userAssignedToPathGame($userId, (int)$game->id)) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this path game'], 403);
            }
        }

        $assignmentTable = $this->pathAssignmentTable();

        // Assigned students (only students)
        $assignedStudentIds = [];
        if ($assignmentTable) {
            try {
                $assignedUsersQ = DB::table($assignmentTable . ' as a')
                    ->join('users as u', 'u.id', '=', 'a.user_id')
                    ->where('a.path_game_id', (int)$game->id);

                if (Schema::hasColumn($assignmentTable, 'deleted_at')) $assignedUsersQ->whereNull('a.deleted_at');

                if (Schema::hasColumn($assignmentTable, 'status')) {
                    $assignedUsersQ->where(function ($w) {
                        $w->whereNull('a.status')->orWhereIn('a.status', ['active', 'assigned', 'enabled', 1, '1']);
                    });
                }

                if (Schema::hasColumn('users', 'role')) {
                    $assignedUsersQ->whereRaw("LOWER(u.role) = 'student'");
                }

                $assignedStudentIds = $assignedUsersQ->pluck('u.id')->map(fn($x) => (int)$x)->values()->all();
            } catch (\Throwable $e) {
                $assignedStudentIds = [];
            }
        }

        // Attempts
        $attemptsQ = DB::table('path_game_results as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.path_game_id', (int)$game->id);

        if (Schema::hasColumn('path_game_results', 'deleted_at')) $attemptsQ->whereNull('r.deleted_at');

        if (Schema::hasColumn('users', 'role')) {
            $attemptsQ->whereRaw("LOWER(u.role) = 'student'");
        }

        if (!empty($assignedStudentIds)) {
            $attemptsQ->whereIn('r.user_id', $assignedStudentIds);
        }

        $qText = trim((string)$request->query('q', ''));
        if ($qText !== '') {
            $attemptsQ->where(function ($w) use ($qText) {
                $w->where('u.name', 'like', "%{$qText}%")
                  ->orWhere('u.email', 'like', "%{$qText}%");
            });
        }

        $attemptsQ->orderByDesc(Schema::hasColumn('path_game_results', 'created_at') ? 'r.created_at' : 'r.id');

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
            'r.user_answer_json',
            'r.created_at as result_created_at',
        ])->get()->map(function ($a) {
            $raw = (string)($a->user_answer_json ?? '');
            $decoded = [];
            try {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) $decoded = [];
            } catch (\Throwable $e) {
                $decoded = [];
            }

            $score = (float)($a->score ?? 0);
            $pct = (float)min(100, max(0, $score));

            return [
                'result_id' => (int)$a->result_id,
                'result_uuid' => (string)($a->result_uuid ?? ''),
                'student_id' => (int)$a->student_id,
                'student_name' => (string)($a->student_name ?? ''),
                'student_email' => (string)($a->student_email ?? ''),
                'attempt_no' => (int)($a->attempt_no ?? 1),
                'score' => $score,
                'percentage' => $pct,
                'time_taken_ms' => (int)($a->time_taken_ms ?? 0),
                'status' => (string)($a->status ?? ''),
                'user_answer_json' => $raw,
                'user_answer' => $decoded,
                'result_created_at' => $a->result_created_at
                    ? Carbon::parse($a->result_created_at)->toDateTimeString()
                    : null,
            ];
        })->values();

        $totalAttempts = $attempts->count();
        $uniqueAttempted = $attempts->pluck('student_id')->unique()->count();
        $totalAssignedStudents = !empty($assignedStudentIds) ? count($assignedStudentIds) : $uniqueAttempted;

        return response()->json([
            'success' => true,
            'data' => [
                'game' => [
                    'id' => (int)$game->id,
                    'uuid' => (string)$game->uuid,
                    'title' => (string)$game->title,
                    'time_limit_sec' => (int)($game->time_limit_sec ?? 0),
                    'grid_dim' => (int)($game->grid_dim ?? 0),
                ],
                'stats' => [
                    'total_attempts' => (int)$totalAttempts,
                    'unique_attempted' => (int)$uniqueAttempted,
                    'total_assigned_students' => (int)$totalAssignedStudents,
                ],
                'attempts' => $attempts,
            ]
        ], 200);
    }

    /* ============================================================
     | ✅ Publish / Unpublish / Bulk Publish (Door-style)
     *============================================================ */

    private function pathResultByKey(string $key)
    {
        $q = DB::table('path_game_results')->whereNull('deleted_at');
        if (ctype_digit($key)) $q->where('id', (int)$key);
        else $q->where('uuid', $key);
        return $q->first();
    }

    private function setPublishToStudent(Request $request, string $resultKey, int $to)
    {
        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        if (!in_array($role, ['admin','superadmin','super_admin','director','examiner','instructor'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!Schema::hasColumn('path_game_results', 'publish_to_student')) {
            return response()->json(['success' => false, 'message' => 'publish_to_student column missing in DB'], 500);
        }

        $row = $this->pathResultByKey($resultKey);
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Result not found'], 404);
        }

        if (in_array($role, ['examiner','instructor'], true)) {
            if (!$this->userAssignedToPathGame($userId, (int)$row->path_game_id)) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this path game'], 403);
            }
        }

        DB::table('path_game_results')
            ->where('id', (int)$row->id)
            ->update([
                'publish_to_student' => (int)$to,
                'updated_at' => now(),
                'updated_at_ip' => $request->ip(),
            ]);

        return response()->json([
            'success' => true,
            'message' => $to ? 'Published to student' : 'Unpublished from student',
            'data' => [
                'result_id' => (int)$row->id,
                'result_uuid' => (string)$row->uuid,
                'publish_to_student' => (int)$to,
            ]
        ], 200);
    }

    public function publishResultToStudent(Request $request, string $resultKey)
    {
        return $this->setPublishToStudent($request, $resultKey, 1);
    }

    public function unpublishResultToStudent(Request $request, string $resultKey)
    {
        return $this->setPublishToStudent($request, $resultKey, 0);
    }

    public function bulkPublishAny(Request $request)
    {
        $actor  = $this->actor($request);
        $role   = $this->normalizeRole($actor['role'] ?? '');
        $userId = (int)($actor['id'] ?? 0);

        if (!in_array($role, ['admin','superadmin','super_admin','director','examiner','instructor'], true)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!Schema::hasColumn('path_game_results', 'publish_to_student')) {
            return response()->json(['success' => false, 'message' => 'publish_to_student column missing in DB'], 500);
        }

        $validator = Validator::make($request->all(), [
            'result_uuids' => ['required','array','min:1'],
            'result_uuids.*' => ['required','string'],
            'publish_to_student' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uuids = array_values(array_filter((array)$request->input('result_uuids', [])));
        $to    = $this->boolToTiny($request->input('publish_to_student'));

        $q = DB::table('path_game_results')
            ->whereNull('deleted_at')
            ->whereIn('uuid', $uuids);

        if (in_array($role, ['examiner','instructor'], true)) {
            // restrict to assigned games if assignment table exists
            $table = $this->pathAssignmentTable();
            if ($table) {
                $assignedGameIds = DB::table($table)
                    ->where('user_id', $userId)
                    ->when(Schema::hasColumn($table, 'deleted_at'), fn($x) => $x->whereNull('deleted_at'))
                    ->pluck('path_game_id')
                    ->map(fn($x) => (int)$x)
                    ->values()
                    ->all();

                if (!empty($assignedGameIds)) {
                    $q->whereIn('path_game_id', $assignedGameIds);
                } else {
                    return response()->json(['success' => false, 'message' => 'You are not assigned to any path games'], 403);
                }
            }
        }

        $affected = $q->update([
            'publish_to_student' => $to,
            'updated_at' => now(),
            'updated_at_ip' => $request->ip(),
        ]);

        $updated = DB::table('path_game_results')
            ->whereIn('uuid', $uuids)
            ->whereNull('deleted_at')
            ->get(['id', 'uuid', 'publish_to_student', 'updated_at'])
            ->map(fn($r) => [
                'result_id' => (int)$r->id,
                'result_uuid' => (string)$r->uuid,
                'publish_to_student' => (int)$r->publish_to_student,
                'updated_at' => $r->updated_at ? Carbon::parse($r->updated_at)->toDateTimeString() : null,
            ])->values();

        return response()->json([
            'success' => true,
            'message' => $to ? 'Bulk published to students' : 'Bulk unpublished from students',
            'affected' => (int)$affected,
            'data' => $updated,
        ], 200);
    }

    /* ============================================================
     | ✅ EXPORT CSV (Door-style)
     | GET /api/path-game/result/export
     *============================================================ */

    public function export(Request $request)
    {
        Log::info('PathGameResult.export: start', [
            'ip' => $request->ip(),
            'query' => $request->query(),
        ]);

        try {
            $actor  = $this->actor($request);
            $role   = $this->normalizeRole($actor['role'] ?? '');
            $userId = (int)($actor['id'] ?? 0);

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
                $arr = is_array($v) ? $v : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);
                $out = [];
                foreach ($arr as $item) {
                    $item = $clean($item);
                    if ($item !== null) $out[] = (string)$item;
                }
                return array_values(array_unique($out));
            };

            $toIntList = function ($v) use ($clean) {
                if ($v === null) return [];
                $arr = is_array($v) ? $v : preg_split('/[,\|]/', (string)$v, -1, PREG_SPLIT_NO_EMPTY);
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

            $hasPublish = Schema::hasColumn('path_game_results', 'publish_to_student');

            $hasFolderTable = Schema::hasTable('user_folders');
            $hasFolderDeletedAt = $hasFolderTable ? Schema::hasColumn('user_folders', 'deleted_at') : false;

            $hasUserDeletedAt = Schema::hasColumn('users', 'deleted_at');

            // ✅ phone column safe
            $phoneCol = 'u.phone_number';
            if (!Schema::hasColumn('users', 'phone_number')) {
                $phoneCol = Schema::hasColumn('users', 'phone') ? 'u.phone' : null;
            }

            $q = DB::table('path_game_results as pgr')
                ->join('path_games as pg', 'pgr.path_game_id', '=', 'pg.id')
                ->join('users as u', 'pgr.user_id', '=', 'u.id')
                ->whereNull('pgr.deleted_at')
                ->whereNull('pg.deleted_at');

            if ($hasUserDeletedAt) $q->whereNull('u.deleted_at');

            if ($hasFolderTable) {
                $q->leftJoin('user_folders as uf', function ($j) use ($hasFolderDeletedAt) {
                    $j->on('uf.id', '=', 'u.user_folder_id');
                    if ($hasFolderDeletedAt) $j->whereNull('uf.deleted_at');
                });
            }

            $pctExpr = 'LEAST(COALESCE(pgr.score,0), 100.0)';

            $select = [
                'u.name as student_name',
                'u.email as student_email',
                DB::raw(($phoneCol ? "$phoneCol" : "NULL") . " as phone_no"),
                'pg.title as game_title',
                'pg.time_limit_sec',
                'pgr.score',
                'pgr.attempt_no',
                'pgr.time_taken_ms',
                DB::raw("ROUND($pctExpr, 2) as percentage_pct"),
            ];

            if ($hasFolderTable) $select[] = 'uf.title as folder_title';
            else $select[] = DB::raw('NULL as folder_title');

            $q->select($select);

            // Student visibility
            if ($role === 'student') {
                $q->where('pgr.user_id', $userId);
                if ($hasPublish) $q->where('pgr.publish_to_student', 1);
            }

            // Filters (same as index)
            $gameKeys = $toStrList($request->query('path_game_id'));
            if (!empty($gameKeys)) {
                $ids = [];
                $uuids = [];
                foreach ($gameKeys as $v) {
                    if (is_numeric($v)) $ids[] = (int)$v;
                    else $uuids[] = (string)$v;
                }
                $q->where(function ($w) use ($ids, $uuids) {
                    if (!empty($ids)) $w->orWhereIn('pg.id', array_values(array_unique($ids)));
                    if (!empty($uuids)) $w->orWhereIn('pg.uuid', array_values(array_unique($uuids)));
                });
            }

            $gameUuids = $toStrList($request->query('game_uuid'));
            if (!empty($gameUuids)) $q->whereIn('pg.uuid', $gameUuids);

            $emailTerms = $toStrList($request->query('student_email'));
            if (!empty($emailTerms)) {
                $q->where(function ($w) use ($emailTerms) {
                    foreach ($emailTerms as $t) $w->orWhere('u.email', 'like', "%{$t}%");
                });
            }

            $folderIds = $toIntList($request->query('user_folder_id'));
            if (!empty($folderIds)) $q->whereIn('u.user_folder_id', $folderIds);

            $folderTitles = $toStrList($request->query('folder_title'));
            if (!empty($folderTitles) && $hasFolderTable) {
                $q->where(function ($w) use ($folderTitles) {
                    foreach ($folderTitles as $t) $w->orWhere('uf.title', 'like', "%{$t}%");
                });
            }

            $txt = $clean($request->query('q', null));
            $alt = $clean($request->query('search', null));
            $search = $txt ?? $alt;

            if ($search !== null) {
                $q->where(function ($w) use ($search) {
                    $w->where('u.name', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('pg.title', 'like', "%{$search}%")
                      ->orWhere('pgr.uuid', 'like', "%{$search}%");
                });
            }

            $attemptStatus = $toStrList($request->query('attempt_status'));
            if (!empty($attemptStatus)) $q->whereIn('pgr.status', $attemptStatus);

            $attemptNos = $toIntList($request->query('attempt_no'));
            if (!empty($attemptNos)) $q->whereIn('pgr.attempt_no', $attemptNos);

            if ($hasPublish && $role !== 'student') {
                $pub = $toBool01($request->query('publish_to_student'));
                if ($pub !== null) $q->where('pgr.publish_to_student', $pub);
            }

            $from = $clean($request->query('from'));
            $to   = $clean($request->query('to'));

            if ($from !== null || $to !== null) {
                try {
                    $start = $from ? Carbon::parse($from)->startOfDay() : null;
                    $end   = $to ? Carbon::parse($to)->endOfDay() : null;

                    if ($start && $end) {
                        if ($start->gt($end)) { $tmp = $start; $start = $end; $end = $tmp; }
                        $q->whereBetween('pgr.created_at', [$start, $end]);
                    } elseif ($start) {
                        $q->where('pgr.created_at', '>=', $start);
                    } elseif ($end) {
                        $q->where('pgr.created_at', '<=', $end);
                    }
                } catch (\Throwable $e) {}
            }

            $minPct = $toFloat($request->query('min_percentage'));
            $maxPct = $toFloat($request->query('max_percentage'));

            if ($minPct !== null && $maxPct !== null) {
                if ($minPct > $maxPct) { $tmp = $minPct; $minPct = $maxPct; $maxPct = $tmp; }
                $q->whereRaw("$pctExpr BETWEEN ? AND ?", [$minPct, $maxPct]);
            } else {
                if ($minPct !== null) $q->whereRaw("$pctExpr >= ?", [$minPct]);
                if ($maxPct !== null) $q->whereRaw("$pctExpr <= ?", [$maxPct]);
            }

            // Sorting
            $sort = (string)$request->query('sort', '-result_created_at');
            $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $key  = ltrim($sort, '-');

            $sortMap = [
                'student_name'      => 'u.name',
                'student_email'     => 'u.email',
                'game_title'        => 'pg.title',
                'score'             => 'pgr.score',
                'percentage'        => DB::raw($pctExpr),
                'attempt_no'        => 'pgr.attempt_no',
                'result_created_at' => 'pgr.created_at',
                'folder_title'      => $hasFolderTable ? 'uf.title' : 'u.name',
            ];

            $orderCol = $sortMap[$key] ?? 'pgr.created_at';
            $q->orderBy($orderCol, $dir)->orderBy('pgr.id', 'desc');

            $rows = $q->get();

            if ($rows->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No results found to export',
                ], 404);
            }

            $filename = 'path_game_results_' . Carbon::now()->format('Y-m-d_His') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($rows) {
                $file = fopen('php://output', 'w');

                // UTF-8 BOM
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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

                foreach ($rows as $r) {
                    $percentage = isset($r->percentage_pct) ? (float)$r->percentage_pct : 0;

                    $timeTakenMs = (int)($r->time_taken_ms ?? 0);
                    $timeTakenSec = $timeTakenMs > 0 ? round($timeTakenMs / 1000, 2) : 0;

                    $efficiency = 0;
                    $timeLimitSec = (int)($r->time_limit_sec ?? 0);

                    if ($timeLimitSec > 0 && $timeTakenSec > 0) {
                        $efficiency = min(100, round((($timeLimitSec - $timeTakenSec) / $timeLimitSec) * 100, 2));
                        if ($efficiency < 0) $efficiency = 0;
                    }

                    fputcsv($file, [
                        $r->student_name ?? '',
                        $r->student_email ?? '',
                        $r->phone_no ?? '',
                        $r->folder_title ?? '',
                        $r->game_title ?? '',
                        number_format($percentage, 2),
                        (float)($r->score ?? 0),
                        (int)($r->attempt_no ?? 0),
                        $timeTakenSec,
                        number_format($efficiency, 2),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Throwable $e) {
            Log::error('PathGameResult.export: exception', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while exporting results',
            ], 500);
        }
    }

    /* ============================================================
     | ✅ My Results (kept)
     | GET /api/path-games/{gameKey}/my-results
     *============================================================ */

    public function myResults(Request $request, string $gameKey)
    {
        $userId = $this->actorId($request);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $game = $this->findGame($gameKey);
        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'Game not found.',
            ], 404);
        }

        $results = DB::table('path_game_results')
            ->where('path_game_id', $game->id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('attempt_no')
            ->get()
            ->map(fn($r) => $this->mapResultRow($r));

        return response()->json([
            'success' => true,
            'message' => 'Your results fetched successfully.',
            'data' => [
                'game' => [
                    'id' => $game->id,
                    'uuid' => $game->uuid ?? null,
                    'title' => $game->title,
                    'max_attempts' => $game->max_attempts,
                ],
                'results' => $results,
                'summary' => [
                    'total_attempts' => $results->count(),
                    'remaining_attempts' => max(0, (int)$game->max_attempts - $results->count()),
                    'best_score' => $results->max('score') ?? 0,
                    'wins' => $results->where('status', 'win')->count(),
                ]
            ]
        ], 200);
    }
}
