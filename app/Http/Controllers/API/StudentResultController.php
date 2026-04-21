<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentResultController extends Controller
{
    /**
     * Supported types (query param: ?type=...)
     * - door_game
     * - quizz
     * - bubble_game
     * - path_game
     */
    private const TYPES = ['door_game','quizz','bubble_game','path_game'];

    /* =========================================================
     | Auth helper
     |========================================================= */
    private function actor(Request $request): array
    {
        return [
            'role' => (string) ($request->attributes->get('auth_role') ?? ''),
            'type' => (string) ($request->attributes->get('auth_tokenable_type') ?? ''),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    /* =========================================================
     | Schema cache helpers (speed)
     |========================================================= */
    private function schemaHasTable(string $table): bool
    {
        return Cache::remember("sr:hasTable:$table", 6 * 3600, fn() => Schema::hasTable($table));
    }

    private function schemaHasColumn(string $table, string $col): bool
    {
        return Cache::remember(
            "sr:hasCol:$table:$col",
            6 * 3600,
            fn() => $this->schemaHasTable($table) && Schema::hasColumn($table, $col)
        );
    }

    private function firstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $t) {
            if ($this->schemaHasTable($t)) return $t;
        }
        return null;
    }

    private function safeCol(?string $table, string $alias, string $col, string $fallbackSql = "NULL"): string
    {
        if ($table && $this->schemaHasColumn($table, $col)) return "{$alias}.{$col}";
        return $fallbackSql;
    }

    private function safeRCol(string $table, string $alias, string $col, string $fallbackSql = "NULL"): string
    {
        if ($this->schemaHasColumn($table, $col)) return "{$alias}.{$col}";
        return $fallbackSql;
    }

    /**
     * ✅ Pick a "best" title expression from the joined game/quiz table.
     * Order:
     * title -> quiz_name -> name -> quiz_title -> game_title -> fallback
     */
    private function pickTitleExpr(?string $gameTable, string $alias, string $fallbackSql): string
    {
        if (!$gameTable) return $fallbackSql;

        if ($this->schemaHasColumn($gameTable, 'title'))     return "{$alias}.title";
        if ($this->schemaHasColumn($gameTable, 'quiz_name')) return "{$alias}.quiz_name";
        if ($this->schemaHasColumn($gameTable, 'name'))      return "{$alias}.name";

        if ($this->schemaHasColumn($gameTable, 'quiz_title')) return "{$alias}.quiz_title";
        if ($this->schemaHasColumn($gameTable, 'game_title')) return "{$alias}.game_title";

        return $fallbackSql;
    }

    /* =========================================================
     | Query builders per type
     |  - Always returns: module, game_id, game_uuid, game_title,
     |    result_id, result_uuid,
     |    attempt_no (latest attempt no/id-ish),
     |    attempt_total_count (TOTAL attempts for this user+game),
     |    score, accuracy, publish_to_student, result_created_at
     |========================================================= */

    private function queryDoor(int $userId)
    {
        if (!$this->schemaHasTable('door_game_results')) return null;

        $resultTable   = 'door_game_results';
        $doorGameTable = $this->firstExistingTable(['door_game', 'door_games']);

        $hasPub       = $this->schemaHasColumn($resultTable, 'publish_to_student');
        $hasStatus    = $this->schemaHasColumn($resultTable, 'status');
        $hasDeletedAt = $this->schemaHasColumn($resultTable, 'deleted_at');
        $hasAttemptNo = $this->schemaHasColumn($resultTable, 'attempt_no');

        // ✅ attempts stats subquery (COUNT + MAX(attempt_no))
        $ac = DB::table($resultTable . ' as rr')
            ->select([
                'rr.door_game_id',
                DB::raw('COUNT(*) as attempt_total_count'),
                DB::raw('COALESCE(MAX(' . ($hasAttemptNo ? 'rr.attempt_no' : 'rr.id') . '), 0) as max_attempt_no'),
            ])
            ->where('rr.user_id', $userId);

        if ($hasDeletedAt) $ac->whereNull('rr.deleted_at');
        if ($hasPub) {
            $ac->where('rr.publish_to_student', 1);
        } elseif ($hasStatus) {
            $ac->where('rr.status', '!=', 'in_progress');
        }

        $ac->groupBy('rr.door_game_id');

        $q = DB::table($resultTable . ' as r');

        if ($doorGameTable) $q->leftJoin($doorGameTable . ' as g', 'g.id', '=', 'r.door_game_id');
        $q->leftJoinSub($ac, 'ac', function ($join) {
            $join->on('ac.door_game_id', '=', 'r.door_game_id');
        });

        if ($hasDeletedAt) $q->whereNull('r.deleted_at');
        $q->where('r.user_id', $userId);

        if ($hasPub) $q->where('r.publish_to_student', 1);
        elseif ($hasStatus) $q->where('r.status', '!=', 'in_progress');

        $titleExpr = $this->pickTitleExpr($doorGameTable, 'g', "CONCAT('Door Game #', r.door_game_id)");

        $attemptNoExpr = $hasAttemptNo ? "r.attempt_no" : "r.id";

        $q->selectRaw("
            'door_game' as module,
            " . ($doorGameTable ? "g.id" : "r.door_game_id") . " as game_id,
            " . $this->safeCol($doorGameTable, 'g', 'uuid', "NULL") . " as game_uuid,
            {$titleExpr} as game_title,

            r.id as result_id,
            r.uuid as result_uuid,

            COALESCE({$attemptNoExpr}, ac.max_attempt_no, 1) as attempt_no,
            COALESCE(ac.attempt_total_count, 0) as attempt_total_count,

            " . $this->safeRCol($resultTable, 'r', 'score', "0") . " as score,
            " . $this->safeRCol($resultTable, 'r', 'accuracy', "NULL") . " as accuracy,

            " . ($hasPub ? "r.publish_to_student" : "1") . " as publish_to_student,
            COALESCE(" . $this->safeRCol($resultTable, 'r', 'result_created_at', "NULL") . ", r.created_at) as result_created_at
        ");

        return $q;
    }

    private function queryBubble(int $userId)
    {
        if (!$this->schemaHasTable('bubble_game_results')) return null;

        $resultTable     = 'bubble_game_results';
        $bubbleGameTable = $this->firstExistingTable(['bubble_game', 'bubble_games']);

        $hasPub       = $this->schemaHasColumn($resultTable, 'publish_to_student');
        $hasStatus    = $this->schemaHasColumn($resultTable, 'status');
        $hasDeletedAt = $this->schemaHasColumn($resultTable, 'deleted_at');
        $hasAttemptNo = $this->schemaHasColumn($resultTable, 'attempt_no');

        $ac = DB::table($resultTable . ' as rr')
            ->select([
                'rr.bubble_game_id',
                DB::raw('COUNT(*) as attempt_total_count'),
                DB::raw('COALESCE(MAX(' . ($hasAttemptNo ? 'rr.attempt_no' : 'rr.id') . '), 0) as max_attempt_no'),
            ])
            ->where('rr.user_id', $userId);

        if ($hasDeletedAt) $ac->whereNull('rr.deleted_at');
        if ($hasPub) {
            $ac->where('rr.publish_to_student', 1);
        } elseif ($hasStatus) {
            $ac->where('rr.status', '!=', 'in_progress');
        }

        $ac->groupBy('rr.bubble_game_id');

        $q = DB::table($resultTable . ' as r');

        if ($bubbleGameTable) $q->leftJoin($bubbleGameTable . ' as g', 'g.id', '=', 'r.bubble_game_id');
        $q->leftJoinSub($ac, 'ac', function ($join) {
            $join->on('ac.bubble_game_id', '=', 'r.bubble_game_id');
        });

        if ($hasDeletedAt) $q->whereNull('r.deleted_at');
        $q->where('r.user_id', $userId);

        if ($hasPub) $q->where('r.publish_to_student', 1);
        elseif ($hasStatus) $q->where('r.status', '!=', 'in_progress');

        $titleExpr = $this->pickTitleExpr($bubbleGameTable, 'g', "CONCAT('Bubble Game #', r.bubble_game_id)");

        $attemptNoExpr = $hasAttemptNo ? "r.attempt_no" : "r.id";

        $q->selectRaw("
            'bubble_game' as module,
            " . ($bubbleGameTable ? "g.id" : "r.bubble_game_id") . " as game_id,
            " . $this->safeCol($bubbleGameTable, 'g', 'uuid', "NULL") . " as game_uuid,
            {$titleExpr} as game_title,

            r.id as result_id,
            r.uuid as result_uuid,

            COALESCE({$attemptNoExpr}, ac.max_attempt_no, 1) as attempt_no,
            COALESCE(ac.attempt_total_count, 0) as attempt_total_count,

            " . $this->safeRCol($resultTable, 'r', 'score', "0") . " as score,
            " . $this->safeRCol($resultTable, 'r', 'accuracy', "NULL") . " as accuracy,

            " . ($hasPub ? "r.publish_to_student" : "1") . " as publish_to_student,
            COALESCE(" . $this->safeRCol($resultTable, 'r', 'result_created_at', "NULL") . ", r.created_at) as result_created_at
        ");

        return $q;
    }

    private function queryPath(int $userId)
    {
        if (!$this->schemaHasTable('path_game_results')) return null;

        $resultTable   = 'path_game_results';
        $pathGameTable = $this->firstExistingTable(['path_games', 'path_game']);

        $hasPub       = $this->schemaHasColumn($resultTable, 'publish_to_student');
        $hasStatus    = $this->schemaHasColumn($resultTable, 'status');
        $hasDeletedAt = $this->schemaHasColumn($resultTable, 'deleted_at');
        $hasAttemptNo = $this->schemaHasColumn($resultTable, 'attempt_no');

        $ac = DB::table($resultTable . ' as rr')
            ->select([
                'rr.path_game_id',
                DB::raw('COUNT(*) as attempt_total_count'),
                DB::raw('COALESCE(MAX(' . ($hasAttemptNo ? 'rr.attempt_no' : 'rr.id') . '), 0) as max_attempt_no'),
            ])
            ->where('rr.user_id', $userId);

        if ($hasDeletedAt) $ac->whereNull('rr.deleted_at');
        if ($hasPub) {
            $ac->where('rr.publish_to_student', 1);
        } elseif ($hasStatus) {
            $ac->where('rr.status', '!=', 'in_progress');
        }

        $ac->groupBy('rr.path_game_id');

        $q = DB::table($resultTable . ' as r');

        if ($pathGameTable) $q->leftJoin($pathGameTable . ' as g', 'g.id', '=', 'r.path_game_id');
        $q->leftJoinSub($ac, 'ac', function ($join) {
            $join->on('ac.path_game_id', '=', 'r.path_game_id');
        });

        if ($hasDeletedAt) $q->whereNull('r.deleted_at');
        $q->where('r.user_id', $userId);

        if ($hasPub) $q->where('r.publish_to_student', 1);
        elseif ($hasStatus) $q->where('r.status', '!=', 'in_progress');

        $titleExpr = $this->pickTitleExpr($pathGameTable, 'g', "CONCAT('Path Game #', r.path_game_id)");

        $attemptNoExpr = $hasAttemptNo ? "r.attempt_no" : "r.id";

        $q->selectRaw("
            'path_game' as module,
            " . ($pathGameTable ? "g.id" : "r.path_game_id") . " as game_id,
            " . $this->safeCol($pathGameTable, 'g', 'uuid', "NULL") . " as game_uuid,
            {$titleExpr} as game_title,

            r.id as result_id,
            r.uuid as result_uuid,

            COALESCE({$attemptNoExpr}, ac.max_attempt_no, 1) as attempt_no,
            COALESCE(ac.attempt_total_count, 0) as attempt_total_count,

            " . $this->safeRCol($resultTable, 'r', 'score', "0") . " as score,
            NULL as accuracy,

            " . ($hasPub ? "r.publish_to_student" : "1") . " as publish_to_student,
            r.created_at as result_created_at
        ");

        return $q;
    }

    private function queryQuizz(int $userId)
    {
        if (!$this->schemaHasTable('quizz_results')) return null;

        $resultTable  = 'quizz_results';
        $quizTable    = $this->firstExistingTable(['quizz', 'quizzes', 'quiz']);

        $hasPub       = $this->schemaHasColumn($resultTable, 'publish_to_student');
        $hasStatus    = $this->schemaHasColumn($resultTable, 'status');
        $hasDeletedAt = $this->schemaHasColumn($resultTable, 'deleted_at');

        // ✅ attempts table (best for real attempt count)
        $attemptTable = $this->schemaHasTable('quizz_attempts') ? 'quizz_attempts' : null;

        // attempts count subquery
        // If quizz_attempts exists, count attempts from it (even if result row is single).
        // Else fallback to counting results.
        if ($attemptTable) {
            $ac = DB::table($attemptTable . ' as aa')
                ->select([
                    'aa.quiz_id',
                    DB::raw('COUNT(*) as attempt_total_count'),
                    DB::raw('COALESCE(MAX(aa.id), 0) as max_attempt_no'),
                ])
                ->where('aa.user_id', $userId)
                ->groupBy('aa.quiz_id');
        } else {
            $ac = DB::table($resultTable . ' as rr')
                ->select([
                    'rr.quiz_id',
                    DB::raw('COUNT(*) as attempt_total_count'),
                    DB::raw('COALESCE(MAX(rr.id), 0) as max_attempt_no'),
                ])
                ->where('rr.user_id', $userId);

            if ($hasDeletedAt) $ac->whereNull('rr.deleted_at');
            if ($hasPub) {
                $ac->where('rr.publish_to_student', 1);
            } elseif ($hasStatus) {
                $ac->where('rr.status', '!=', 'in_progress');
            }

            $ac->groupBy('rr.quiz_id');
        }

        $q = DB::table($resultTable . ' as r');

        if ($quizTable && $this->schemaHasColumn($resultTable, 'quiz_id')) {
            $q->leftJoin($quizTable . ' as g', 'g.id', '=', 'r.quiz_id');
        }

        $q->leftJoinSub($ac, 'ac', function ($join) {
            $join->on('ac.quiz_id', '=', 'r.quiz_id');
        });

        if ($hasDeletedAt) $q->whereNull('r.deleted_at');

        $q->where('r.user_id', $userId);

        if ($hasPub) $q->where('r.publish_to_student', 1);
        elseif ($hasStatus) $q->where('r.status', '!=', 'in_progress');

        $titleExpr = $this->pickTitleExpr($quizTable, 'g', "CONCAT('Quizz #', r.quiz_id)");

        // attempt number: prefer r.attempt_id (your schema), else fallback r.id / max_attempt_no
        $attemptNoExpr = $this->safeRCol($resultTable, 'r', 'attempt_id', "NULL");

        $q->selectRaw("
            'quizz' as module,
            " . ($quizTable ? "g.id" : "r.quiz_id") . " as game_id,
            " . $this->safeCol($quizTable, 'g', 'uuid', $this->safeRCol($resultTable,'r','quiz_uuid',"NULL")) . " as game_uuid,
            {$titleExpr} as game_title,

            r.id as result_id,
            r.uuid as result_uuid,

            COALESCE({$attemptNoExpr}, ac.max_attempt_no, r.id, 1) as attempt_no,
            COALESCE(ac.attempt_total_count, 0) as attempt_total_count,

            " . $this->safeRCol($resultTable, 'r', 'marks_obtained', "0") . " as score,
            " . $this->safeRCol($resultTable, 'r', 'percentage', "NULL") . " as accuracy,

            " . ($hasPub ? "r.publish_to_student" : "1") . " as publish_to_student,
            COALESCE(" . $this->safeRCol($resultTable, 'r', 'released_at', "NULL") . ", r.created_at) as result_created_at
        ");

        return $q;
    }

    private function viewUrl(string $module, string $rid): string
    {
        $rid = rawurlencode($rid);

        if ($module === 'door_game')   return "/decision-making-test/results/{$rid}/view";
        if ($module === 'quizz')       return "/exam/results/{$rid}/view";
        if ($module === 'bubble_game') return "/test/results/{$rid}/view";
        if ($module === 'path_game')   return "/path-game/results/{$rid}/view";
        return '#';
    }

    /* =========================================================
     | GET: /api/student-results/my
     |========================================================= */
    public function myPublished(Request $request)
    {
        $actor  = $this->actor($request);
        $userId = (int) ($actor['id'] ?? 0);

        if ($userId <= 0) {
            return response()->json(['success' => false, 'message' => 'Unable to resolve user from token.'], 403);
        }

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(10, (int) $request->query('per_page', 20)));
        $search  = trim((string) $request->query('q', ''));
        $type    = strtolower(trim((string) $request->query('type', '')));

        $student = DB::table('users')
            ->select([
                'id',
                $this->schemaHasColumn('users','uuid') ? 'uuid' : DB::raw("NULL as uuid"),
                $this->schemaHasColumn('users','name') ? 'name' : DB::raw("NULL as name"),
                $this->schemaHasColumn('users','email') ? 'email' : DB::raw("NULL as email"),
            ])
            ->where('id', $userId)
            ->first();

        $baseQuery = null;

        if ($type !== '') {
            if (!in_array($type, self::TYPES, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid type. Allowed: ' . implode(', ', self::TYPES),
                ], 422);
            }

            if ($type === 'door_game')   $baseQuery = $this->queryDoor($userId);
            if ($type === 'quizz')       $baseQuery = $this->queryQuizz($userId);
            if ($type === 'bubble_game') $baseQuery = $this->queryBubble($userId);
            if ($type === 'path_game')   $baseQuery = $this->queryPath($userId);

            if (!$baseQuery) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => ['page' => $page,'per_page' => $perPage,'has_more' => false],
                ]);
            }
        } else {
            $qs = array_filter([
                $this->queryDoor($userId),
                $this->queryQuizz($userId),
                $this->queryBubble($userId),
                $this->queryPath($userId),
            ]);

            if (!$qs) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => ['page' => $page,'per_page' => $perPage,'has_more' => false],
                ]);
            }

            $baseQuery = array_shift($qs);
            foreach ($qs as $q) $baseQuery->unionAll($q);
        }

        $outer = DB::query()->fromSub($baseQuery, 'x');

        if ($search !== '') {
            $outer->where('x.game_title', 'like', "%{$search}%");
        }

        $outer->orderByDesc('x.result_created_at');

        $offset = ($page - 1) * $perPage;
        $rows   = $outer->offset($offset)->limit($perPage + 1)->get();

        $hasMore = $rows->count() > $perPage;
        if ($hasMore) $rows = $rows->take($perPage);

        $data = $rows->map(function ($x) use ($student) {
            $rid = (string) ($x->result_uuid ?? '');

            return [
                'module'   => (string) $x->module,
                'view_url' => $this->viewUrl((string) $x->module, $rid),

                'student' => [
                    'id'    => (int) ($student->id ?? 0),
                    'uuid'  => $student->uuid ?? null,
                    'name'  => $student->name ?? null,
                    'email' => $student->email ?? null,
                ],

                // ✅ ALWAYS return proper title (not only ids)
                'game' => [
                    'id'    => (int) ($x->game_id ?? 0),
                    'uuid'  => $x->game_uuid,
                    'title' => $x->game_title,
                ],

                // ✅ attempt_no + attempt_total_count (for your 2nd column UI)
                'result' => [
                    'id' => (int) ($x->result_id ?? 0),
                    'uuid' => $x->result_uuid,

                    // latest attempt indicator (attempt_no / attempt_id / id)
                    'attempt_no' => (int) ($x->attempt_no ?? 1),

                    // ✅ TOTAL attempts done by this user for this quiz/game
                    'attempt_total_count' => (int) ($x->attempt_total_count ?? 0),

                    'score' => $x->score,
                    'accuracy' => $x->accuracy,
                    'publish_to_student' => (int) ($x->publish_to_student ?? 1),
                    'result_created_at' => $x->result_created_at,
                ],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => $hasMore,
            ],
        ]);
    }
}
