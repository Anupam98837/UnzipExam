<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MasterResultController extends Controller
{
    /**
     * Auto-detect your results tables for bubble + door
     * (Quiz is fixed = quizz_results)
     */
    private function detectTables(): array
{
    $quiz = Schema::hasTable('quizz_results') ? 'quizz_results' : null;

    // Bubble candidates
    $bubble = null;
    foreach ([
        'bubble_game_results',
        'bubble_results',
        'bubble_game_attempts',
        'bubble_attempt_results'
    ] as $t) {
        if (Schema::hasTable($t)) { $bubble = $t; break; }
    }

    // ✅ Door candidates (ADD MORE NAMES)
    $door = null;
    foreach ([
        'door_game_results',
        'open_door_results',
        'door_results',
        'decision_making_test_results',
        'decision_making_results',
        'decision_test_results',
        'dm_test_results'
    ] as $t) {
        if (Schema::hasTable($t)) { $door = $t; break; }
    }

    return compact('quiz', 'bubble', 'door');
}

private function firstExistingCol(string $table, array $candidates): ?string
{
    foreach ($candidates as $c) {
        if (Schema::hasColumn($table, $c)) return $c;
    }
    return null;
}

private function detectUserFkCol(string $table): ?string
{
    // ✅ user reference column can vary
    return $this->firstExistingCol($table, [
        'user_id',
        'student_id',
        'candidate_id',
        'user',
        'uid',
    ]);
}

private function detectTimeCol(string $table): ?string
{
    // ✅ attempt time can vary
    return $this->firstExistingCol($table, [
        'total_time',
        'time_taken',
        'time_sec',
        'time_seconds',
        'duration',
        'duration_sec',
    ]);
}

private function detectDoorEfficiencyCols(string $table): array
{
    return [
        'time_eff'  => $this->firstExistingCol($table, ['time_efficiency', 'time_eff', 'time_eff_pct']),
        'total_eff' => $this->firstExistingCol($table, ['total_efficiency', 'total_eff', 'efficiency', 'efficiency_pct']),
    ];
}

    /**
     * Build safe phone expression (prevents SQL error if column missing)
     */
    private function phoneExpr(string $alias = 'u'): string
    {
        $parts = [];

        if (Schema::hasColumn('users', 'phone_number')) $parts[] = "{$alias}.phone_number";
        if (Schema::hasColumn('users', 'phone_no'))     $parts[] = "{$alias}.phone_no";
        if (Schema::hasColumn('users', 'phone'))        $parts[] = "{$alias}.phone";

        $parts[] = "''";
        return "COALESCE(" . implode(',', $parts) . ")";
    }

    /**
     * Detect scoring columns for non-bubble tables
     */
    private function detectScoreCols(string $table): array
    {
        $pct = Schema::hasColumn($table, 'percentage') ? 'percentage' : null;

        $obtained = null;
        foreach (['marks_obtained', 'score', 'points', 'points_scored'] as $c) {
            if (Schema::hasColumn($table, $c)) { $obtained = $c; break; }
        }

        $total = null;
        foreach (['total_marks', 'total', 'max_marks', 'total_points'] as $c) {
            if (Schema::hasColumn($table, $c)) { $total = $c; break; }
        }

        return [
            'pct' => $pct,
            'obt' => $obtained,
            'tot' => $total,
        ];
    }

    /**
     * Row % expression (per attempt, NOT AVG)
     */
    private function rowPctExpr(string $alias, array $cols): string
    {
        if ($cols['pct']) {
            return "COALESCE({$alias}.{$cols['pct']}, NULL)";
        }

        if ($cols['obt'] && $cols['tot']) {
            return "(CASE
                WHEN COALESCE({$alias}.{$cols['tot']},0) > 0
                THEN ({$alias}.{$cols['obt']} / {$alias}.{$cols['tot']}) * 100
                ELSE NULL
            END)";
        }

        return "NULL";
    }

    /**
     * AVG% expression safely from rowPctExpr()
     */
    private function avgPctExpr(string $alias, array $cols): string
    {
        return "AVG(" . $this->rowPctExpr($alias, $cols) . ")";
    }

    /**
     * Special Bubble AVG% for bubble_game_results (accuracy using bubble_game_questions)
     */
    private function bubbleAccuracyAvgExpr(): string
    {
        // score / total_points OR total_questions
        return "AVG(
            CASE
                WHEN COALESCE(qs.total_points,0) > 0 THEN (br.score * 100.0 / qs.total_points)
                WHEN COALESCE(qs.total_questions,0) > 0 THEN (br.score * 100.0 / qs.total_questions)
                ELSE NULL
            END
        )";
    }

    /**
     * Special Bubble Row% for bubble_game_results
     */
    private function bubbleAccuracyRowExpr(): string
    {
        return "(CASE
            WHEN COALESCE(qs.total_points,0) > 0 THEN (br.score * 100.0 / qs.total_points)
            WHEN COALESCE(qs.total_questions,0) > 0 THEN (br.score * 100.0 / qs.total_questions)
            ELSE NULL
        END)";
    }

    /**
     * GET /api/reports/master-results
     */
    public function index(Request $request)
    {
        $tables = $this->detectTables();

        if (!$tables['quiz']) {
            return response()->json([
                'success' => false,
                'message' => 'quizz_results table not found',
            ], 500);
        }

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(200, (int) $request->query('per_page', 20)));
        $search  = trim((string) $request->query('search', ''));
        $sort    = (string) $request->query('sort', 'overall_desc');
        $export  = (string) $request->query('export', '');

        $folderId = $request->query('user_folder_id', null);
        if ($folderId === null) $folderId = $request->query('folder_id', null);

        // ======================================================
        // QUIZ aggregate (attempt_id optional)
        // ======================================================
        $quizAttemptCol = Schema::hasColumn('quizz_results', 'attempt_id') ? 'attempt_id' : 'id';

        $quizAgg = DB::table('quizz_results as qr')
            ->select([
                'qr.user_id',
                DB::raw('ROUND(AVG(qr.percentage), 2) as quiz_avg_pct'),
                DB::raw("COUNT(DISTINCT qr.{$quizAttemptCol}) as quiz_attempts"),
                DB::raw('MAX(qr.created_at) as quiz_last_at'),
            ])
            ->groupBy('qr.user_id');

        // soft delete guard if exists
        if (Schema::hasColumn('quizz_results', 'deleted_at')) {
            $quizAgg->whereNull('qr.deleted_at');
        }

        // ======================================================
        // BUBBLE aggregate
        // ======================================================
        $bubbleAgg = null;

        if ($tables['bubble']) {
            // ✅ If bubble_game_results exists, compute using bubble_game_questions stats
            if ($tables['bubble'] === 'bubble_game_results' && Schema::hasTable('bubble_game_questions')) {

                $qStats = DB::table('bubble_game_questions as bq')
                    ->selectRaw('bq.bubble_game_id, COUNT(*) as total_questions, COALESCE(SUM(bq.points),0) as total_points')
                    ->where('bq.status', 'active')
                    ->groupBy('bq.bubble_game_id');

                $bubbleAgg = DB::table('bubble_game_results as br')
                    ->leftJoinSub($qStats, 'qs', function ($j) {
                        $j->on('qs.bubble_game_id', '=', 'br.bubble_game_id');
                    })
                    ->select([
                        'br.user_id',
                        DB::raw('ROUND(' . $this->bubbleAccuracyAvgExpr() . ', 2) as bubble_avg_pct'),
                        DB::raw('COUNT(*) as bubble_attempts'),
                        DB::raw('MAX(br.created_at) as bubble_last_at'),
                    ])
                    ->groupBy('br.user_id');

                if (Schema::hasColumn('bubble_game_results', 'deleted_at')) {
                    $bubbleAgg->whereNull('br.deleted_at');
                }

            } else {
                // fallback generic
                $bubbleCols = $this->detectScoreCols($tables['bubble']);

                $bubbleAgg = DB::table($tables['bubble'] . ' as br')
                    ->select([
                        'br.user_id',
                        DB::raw('ROUND(' . $this->avgPctExpr('br', $bubbleCols) . ', 2) as bubble_avg_pct'),
                        DB::raw('COUNT(*) as bubble_attempts'),
                        DB::raw('MAX(br.created_at) as bubble_last_at'),
                    ])
                    ->groupBy('br.user_id');

                if (Schema::hasColumn($tables['bubble'], 'deleted_at')) {
                    $bubbleAgg->whereNull('br.deleted_at');
                }
            }
        }

      // ======================================================
// DOOR aggregate ✅ (door_game_results specific + SAFE)
// ======================================================
$doorAgg = null;

if ($tables['door'] === 'door_game_results') {

    // ✅ Safe time taken (prefer column, fallback JSON)
    $timeTakenExpr = "COALESCE(
        dr.time_taken_ms,
        CAST(JSON_EXTRACT(dr.user_answer_json,'$.timing.time_taken_ms') AS SIGNED)
    )";

    // ✅ Moves count (prefer moves[], fallback path[])
    $movesCountExpr = "COALESCE(
        JSON_LENGTH(JSON_EXTRACT(dr.user_answer_json,'$.moves')),
        (JSON_LENGTH(JSON_EXTRACT(dr.user_answer_json,'$.path')) - 1)
    )";

    // ✅ SIGNED avoids UNSIGNED underflow crash
    $gridRaw  = "CAST(JSON_EXTRACT(dr.user_answer_json,'$.grid_dim') AS SIGNED)";
    $startRaw = "CAST(JSON_EXTRACT(dr.user_answer_json,'$.start_index') AS SIGNED)";
    $doorRaw  = "CAST(JSON_EXTRACT(dr.user_answer_json,'$.events.door.opened_at_index') AS SIGNED)";

    // sanitize
    $gridExpr  = "NULLIF($gridRaw, 0)";
    $startSafe = "GREATEST(COALESCE($startRaw,1), 1)";
    $doorSafe  = "GREATEST(COALESCE($doorRaw,1), 1)";

    $start0 = "($startSafe - 1)";
    $door0  = "($doorSafe - 1)";

    $sr = "FLOOR($start0 / $gridExpr)";
    $dr = "FLOOR($door0  / $gridExpr)";
    $sc = "MOD($start0, $gridExpr)";
    $dc = "MOD($door0,  $gridExpr)";

    // shortest moves (Manhattan)
    $shortestMovesExpr = "(ABS($sr - $dr) + ABS($sc - $dc))";

    // ✅ Path efficiency
    $pathEffExpr = "CASE
        WHEN $gridExpr IS NULL THEN NULL
        WHEN COALESCE($movesCountExpr,0) <= 0 THEN NULL
        ELSE LEAST(100, ROUND(($shortestMovesExpr * 100.0) / NULLIF($movesCountExpr,0), 2))
    END";

    // ✅ Time efficiency
    $expectedPerMoveMs = 800;
    $expectedTimeExpr  = "($movesCountExpr * $expectedPerMoveMs)";

    $timeEffExpr = "CASE
        WHEN COALESCE($timeTakenExpr,0) <= 0 THEN NULL
        WHEN COALESCE($movesCountExpr,0) <= 0 THEN NULL
        ELSE LEAST(100, ROUND(($expectedTimeExpr * 100.0) / NULLIF($timeTakenExpr,0), 2))
    END";

    // ✅ Total efficiency = 0.5 time + 0.5 path
    $totalEffExpr = "CASE
        WHEN ($timeEffExpr) IS NULL AND ($pathEffExpr) IS NULL THEN NULL
        ELSE ROUND((0.5 * COALESCE(($timeEffExpr),0)) + (0.5 * COALESCE(($pathEffExpr),0)), 2)
    END";

    $doorAgg = DB::table('door_game_results as dr')
        ->select([
            'dr.user_id',

            // ✅ score is the percentage-like (0-100)
DB::raw("ROUND(AVG(dr.score * 100), 2) as door_avg_pct"),
            DB::raw("COUNT(*) as door_attempts"),
            DB::raw("MAX(dr.created_at) as door_last_at"),

            // ✅ Avg time (ms)
            DB::raw("ROUND(AVG($timeTakenExpr), 0) as door_avg_time_ms"),

            // ✅ Avg efficiencies
            DB::raw("ROUND(AVG($timeEffExpr), 2) as door_time_eff"),
            DB::raw("ROUND(AVG($pathEffExpr), 2) as door_path_eff"),
            DB::raw("ROUND(AVG($totalEffExpr), 2) as door_total_eff"),
        ])
        ->whereNull('dr.deleted_at')
        ->groupBy('dr.user_id');

} elseif ($tables['door']) {

    // ✅ Generic fallback
    $doorCols = $this->detectScoreCols($tables['door']);

    $doorAgg = DB::table($tables['door'] . ' as dr')
        ->select([
            'dr.user_id',
            DB::raw('ROUND(' . $this->avgPctExpr('dr', $doorCols) . ', 2) as door_avg_pct'),
            DB::raw('COUNT(*) as door_attempts'),
            DB::raw('MAX(dr.created_at) as door_last_at'),
        ])
        ->groupBy('dr.user_id');

    if (Schema::hasColumn($tables['door'], 'deleted_at')) {
        $doorAgg->whereNull('dr.deleted_at');
    }
}


        // ======================================================
        // MAIN Query: Users + aggregates
        // ======================================================
        $q = DB::table('users as u')
            ->leftJoin('user_folders as uf', 'uf.id', '=', 'u.user_folder_id')
            ->leftJoinSub($quizAgg, 'qa', function ($j) {
                $j->on('qa.user_id', '=', 'u.id');
            });

        if ($bubbleAgg) {
            $q->leftJoinSub($bubbleAgg, 'ba', function ($j) {
                $j->on('ba.user_id', '=', 'u.id');
            });
        }

        if ($doorAgg) {
            $q->leftJoinSub($doorAgg, 'da', function ($j) {
                $j->on('da.user_id', '=', 'u.id');
            });
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            $q->whereNull('u.deleted_at');
        }

        // filter only students (if role columns exist)
        if (Schema::hasColumn('users', 'role')) {
            $q->where('u.role', 'student');
        } elseif (Schema::hasColumn('users', 'role_short_form')) {
            $q->where('u.role_short_form', 'student');
        }

        // search
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('u.name', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%");

                if (Schema::hasColumn('users', 'phone_number')) {
                    $w->orWhere('u.phone_number', 'like', "%{$search}%");
                } elseif (Schema::hasColumn('users', 'phone_no')) {
                    $w->orWhere('u.phone_no', 'like', "%{$search}%");
                }
            });
        }

        // folder filter
        if ($folderId !== null && $folderId !== '' && is_numeric($folderId)) {
            $q->where('u.user_folder_id', (int) $folderId);
        }

        // select columns
        $q->select([
            'u.id as user_id',
            'u.uuid as user_uuid',
            'u.name',
            'u.email',
            DB::raw($this->phoneExpr('u') . " as phone_number"),
            'u.user_folder_id',
            DB::raw("COALESCE(uf.title,'') as folder_name"),

            DB::raw('COALESCE(qa.quiz_avg_pct, NULL) as quiz_avg_pct'),
            DB::raw('COALESCE(qa.quiz_attempts, 0) as quiz_attempts'),
            DB::raw('qa.quiz_last_at'),

            DB::raw($bubbleAgg ? 'COALESCE(ba.bubble_avg_pct, NULL) as bubble_avg_pct' : 'NULL as bubble_avg_pct'),
            DB::raw($bubbleAgg ? 'COALESCE(ba.bubble_attempts, 0) as bubble_attempts' : '0 as bubble_attempts'),
            DB::raw($bubbleAgg ? 'ba.bubble_last_at' : 'NULL as bubble_last_at'),

DB::raw($doorAgg ? 'COALESCE(da.door_avg_pct, NULL) as door_avg_pct' : 'NULL as door_avg_pct'),
DB::raw($doorAgg ? 'COALESCE(da.door_attempts, 0) as door_attempts' : '0 as door_attempts'),
DB::raw($doorAgg ? 'da.door_last_at' : 'NULL as door_last_at'),

DB::raw($doorAgg ? 'da.door_avg_time_ms' : 'NULL as door_avg_time_ms'),
DB::raw($doorAgg ? 'da.door_time_eff' : 'NULL as door_time_eff'),
DB::raw($doorAgg ? 'da.door_path_eff' : 'NULL as door_path_eff'),
DB::raw($doorAgg ? 'da.door_total_eff' : 'NULL as door_total_eff'),

        ]);

        // overall avg% only from available values
        $q->addSelect(DB::raw("
            ROUND((
              COALESCE(qa.quiz_avg_pct, 0) +
              COALESCE(" . ($bubbleAgg ? "ba.bubble_avg_pct" : "NULL") . ", 0) +
              COALESCE(" . ($doorAgg ? "da.door_avg_pct" : "NULL") . ", 0)
            ) / NULLIF(
              (CASE WHEN qa.quiz_avg_pct IS NULL THEN 0 ELSE 1 END) +
              (CASE WHEN " . ($bubbleAgg ? "ba.bubble_avg_pct" : "NULL") . " IS NULL THEN 0 ELSE 1 END) +
              (CASE WHEN " . ($doorAgg ? "da.door_avg_pct" : "NULL") . " IS NULL THEN 0 ELSE 1 END)
            ,0), 2) as overall_avg_pct
        "));

        // last activity
        $q->addSelect(DB::raw("
            GREATEST(
              COALESCE(qa.quiz_last_at, '1970-01-01'),
              COALESCE(" . ($bubbleAgg ? "ba.bubble_last_at" : "'1970-01-01'") . ", '1970-01-01'),
              COALESCE(" . ($doorAgg ? "da.door_last_at" : "'1970-01-01'") . ", '1970-01-01')
            ) as last_activity_at
        "));

        // sort mapping
        switch ($sort) {
            case 'overall_asc':
                $q->orderBy('overall_avg_pct', 'asc');
                break;
            case 'quiz_desc':
                $q->orderBy('quiz_avg_pct', 'desc');
                break;
            case 'bubble_desc':
                $q->orderBy('bubble_avg_pct', 'desc');
                break;
            case 'door_desc':
                $q->orderBy('door_avg_pct', 'desc');
                break;
            case 'recent_desc':
                $q->orderBy('last_activity_at', 'desc');
                break;
            default:
                $q->orderBy('overall_avg_pct', 'desc');
                break;
        }

        $q->orderBy('u.id', 'desc');

        // =======================
        // Export CSV
        // =======================
        if ($export === 'csv') {
            $rows = $q->get();

            $filename = 'master_results_' . Carbon::now()->format('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($out, [
                    'Student Name',
                    'Email',
                    'Phone',
                    'Folder',
                    'Quiz AVG %',
                    'Quiz Attempts',
                    'Bubble AVG %',
                    'Bubble Attempts',
                    'Door AVG %',
                    'Door Attempts',
                    'Overall AVG %',
                    'Last Activity',
                ]);

                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->name ?? '',
                        $r->email ?? '',
                        $r->phone_number ?? '',
                        $r->folder_name ?? '',
                        $r->quiz_avg_pct,
                        $r->quiz_attempts,
                        $r->bubble_avg_pct,
                        $r->bubble_attempts,
                        $r->door_avg_pct,
                        $r->door_attempts,
                        $r->overall_avg_pct,
                        $r->last_activity_at,
                    ]);
                }

                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        // =======================
        // Pagination
        // =======================
        $base = clone $q;

        $total = (clone $base)->count();
        $items = (clone $base)
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'meta' => [
                    'page'        => $page,
                    'per_page'    => $perPage,
                    'total'       => (int) $total,
                    'total_pages' => (int) ceil($total / max(1, $perPage)),
                ]
            ]
        ], 200);
    }

    /**
     * GET /api/reports/master-results/{student_uuid}
     */
    public function showStudent(string $studentUuid)
    {
        $tables = $this->detectTables();

        $student = DB::table('users as u')
            ->select([
                'u.id', 'u.uuid', 'u.name', 'u.email',
                DB::raw($this->phoneExpr('u') . " as phone_number"),
                'u.user_folder_id',
            ])
            ->where('u.uuid', $studentUuid);

        if (Schema::hasColumn('users', 'deleted_at')) {
            $student->whereNull('u.deleted_at');
        }

        $student = $student->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found',
            ], 404);
        }

        $folder = DB::table('user_folders')->where('id', $student->user_folder_id)->value('title');

        // =======================
        // QUIZ attempts
        // =======================
        $quizAttempts = DB::table('quizz_results as r')
            ->join('quizz as qz', 'qz.id', '=', 'r.quiz_id')
            ->where('r.user_id', $student->id);

        if (Schema::hasColumn('quizz_results', 'deleted_at')) {
            $quizAttempts->whereNull('r.deleted_at');
        }

        $quizAttempts = $quizAttempts
            ->orderByDesc('r.created_at')
            ->limit(200)
            ->get([
                DB::raw('qz.quiz_name as title'),
                DB::raw('r.percentage as percentage'),
                DB::raw('CONCAT(r.marks_obtained, " / ", r.total_marks) as score_text'),
                DB::raw('DATE_FORMAT(r.created_at, "%Y-%m-%d %H:%i") as attempted_at'),
            ]);

        // =======================
        // BUBBLE attempts
        // =======================
        $bubbleAttempts = [];
        if ($tables['bubble']) {
            // Best support for bubble_game_results (accuracy)
            if ($tables['bubble'] === 'bubble_game_results' && Schema::hasTable('bubble_game_questions') && Schema::hasTable('bubble_game')) {

                $qStats = DB::table('bubble_game_questions as bq')
                    ->selectRaw('bq.bubble_game_id, COUNT(*) as total_questions, COALESCE(SUM(bq.points),0) as total_points')
                    ->where('bq.status', 'active')
                    ->groupBy('bq.bubble_game_id');

                $bubbleQ = DB::table('bubble_game_results as br')
                    ->join('bubble_game as g', 'g.id', '=', 'br.bubble_game_id')
                    ->leftJoinSub($qStats, 'qs', function ($j) {
                        $j->on('qs.bubble_game_id', '=', 'br.bubble_game_id');
                    })
                    ->where('br.user_id', $student->id);

                if (Schema::hasColumn('bubble_game_results', 'deleted_at')) {
                    $bubbleQ->whereNull('br.deleted_at');
                }

                $bubbleAttempts = $bubbleQ
                    ->orderByDesc('br.created_at')
                    ->limit(200)
                    ->get([
                        DB::raw('g.title as title'),
                        DB::raw('ROUND(' . $this->bubbleAccuracyRowExpr() . ', 2) as percentage'),
                        DB::raw("CONCAT(br.score, ' / ', COALESCE(qs.total_points, qs.total_questions)) as score_text"),
                        DB::raw('DATE_FORMAT(br.created_at, "%Y-%m-%d %H:%i") as attempted_at'),
                    ]);

            } else {
                // Generic fallback
                $cols = $this->detectScoreCols($tables['bubble']);
                $rowExpr = $this->rowPctExpr('br', $cols);

                $bubbleQ = DB::table($tables['bubble'] . ' as br')
                    ->where('br.user_id', $student->id);

                if (Schema::hasColumn($tables['bubble'], 'deleted_at')) {
                    $bubbleQ->whereNull('br.deleted_at');
                }

                $bubbleAttempts = $bubbleQ
                    ->orderByDesc('br.created_at')
                    ->limit(200)
                    ->get([
                        DB::raw("'Bubble Game' as title"),
                        DB::raw("ROUND(($rowExpr),2) as percentage"),
                        DB::raw("'' as score_text"),
                        DB::raw('DATE_FORMAT(br.created_at, "%Y-%m-%d %H:%i") as attempted_at'),
                    ]);
            }
        }

        // =======================
        // DOOR attempts (generic)
        // =======================
       // =======================
// DOOR attempts ✅ FIXED (score + time + efficiencies)
// =======================
$doorAttempts = [];

if ($tables['door'] === 'door_game_results') {

    // ✅ Safe time taken (prefer column, fallback JSON)
    $timeTakenExpr = "COALESCE(
        dr.time_taken_ms,
        CAST(JSON_EXTRACT(dr.user_answer_json,'$.timing.time_taken_ms') AS SIGNED)
    )";

    // ✅ Moves count (prefer moves[], fallback path[])
    $movesCountExpr = "COALESCE(
        JSON_LENGTH(JSON_EXTRACT(dr.user_answer_json,'$.moves')),
        (JSON_LENGTH(JSON_EXTRACT(dr.user_answer_json,'$.path')) - 1)
    )";

    // ✅ Use SIGNED + sanitize to prevent underflow crash
    $gridRaw  = "CAST(JSON_EXTRACT(dr.user_answer_json,'$.grid_dim') AS SIGNED)";
    $startRaw = "CAST(JSON_EXTRACT(dr.user_answer_json,'$.start_index') AS SIGNED)";
    $doorRaw  = "CAST(JSON_EXTRACT(dr.user_answer_json,'$.events.door.opened_at_index') AS SIGNED)";

    $gridExpr  = "NULLIF($gridRaw, 0)";
    $startSafe = "GREATEST(COALESCE($startRaw,1), 1)";
    $doorSafe  = "GREATEST(COALESCE($doorRaw,1), 1)";

    $start0 = "($startSafe - 1)";
    $door0  = "($doorSafe - 1)";

    $sr = "FLOOR($start0 / $gridExpr)";
    $dr = "FLOOR($door0  / $gridExpr)";
    $sc = "MOD($start0, $gridExpr)";
    $dc = "MOD($door0,  $gridExpr)";

    // shortest moves (Manhattan)
    $shortestMovesExpr = "(ABS($sr - $dr) + ABS($sc - $dc))";

    // ✅ Path efficiency
    $pathEffExpr = "CASE
        WHEN $gridExpr IS NULL THEN NULL
        WHEN COALESCE($movesCountExpr,0) <= 0 THEN NULL
        ELSE LEAST(100, ROUND(($shortestMovesExpr * 100.0) / NULLIF($movesCountExpr,0), 2))
    END";

    // ✅ Time efficiency
    $expectedPerMoveMs = 800;
    $expectedTimeExpr  = "($movesCountExpr * $expectedPerMoveMs)";

    $timeEffExpr = "CASE
        WHEN COALESCE($timeTakenExpr,0) <= 0 THEN NULL
        WHEN COALESCE($movesCountExpr,0) <= 0 THEN NULL
        ELSE LEAST(100, ROUND(($expectedTimeExpr * 100.0) / NULLIF($timeTakenExpr,0), 2))
    END";

    // ✅ Total efficiency = 0.5 time + 0.5 path
    $totalEffExpr = "CASE
        WHEN ($timeEffExpr) IS NULL AND ($pathEffExpr) IS NULL THEN NULL
        ELSE ROUND((0.5 * COALESCE(($timeEffExpr),0)) + (0.5 * COALESCE(($pathEffExpr),0)), 2)
    END";

    $doorQ = DB::table('door_game_results as dr')
        ->where('dr.user_id', $student->id);

    if (Schema::hasColumn('door_game_results', 'deleted_at')) {
        $doorQ->whereNull('dr.deleted_at');
    }

    $doorAttempts = $doorQ
        ->orderByDesc('dr.created_at')
        ->limit(200)
        ->get([
            'dr.id as result_id',
            DB::raw("'Door Game' as title"),
            DB::raw("(dr.score * 100) as percentage"),
            DB::raw("CONCAT(dr.score,' / 1') as score_text"),

            DB::raw("ROUND(($timeTakenExpr)/1000, 2) as time_taken_sec"),
            DB::raw("ROUND(($timeEffExpr),2) as time_eff"),
            DB::raw("ROUND(($pathEffExpr),2) as path_eff"),
            DB::raw("ROUND(($totalEffExpr),2) as total_eff"),
            DB::raw('DATE_FORMAT(dr.created_at, "%Y-%m-%d %H:%i") as attempted_at'),
        ]);

} elseif ($tables['door']) {

    // ✅ Generic fallback (other tables)
    $cols = $this->detectScoreCols($tables['door']);
    $rowExpr = $this->rowPctExpr('dr', $cols);

    $doorQ = DB::table($tables['door'] . ' as dr')
        ->where('dr.user_id', $student->id);

    if (Schema::hasColumn($tables['door'], 'deleted_at')) {
        $doorQ->whereNull('dr.deleted_at');
    }

    $doorAttempts = $doorQ
        ->orderByDesc('dr.created_at')
        ->limit(200)
        ->get([
            'dr.id as result_id',
            DB::raw("'Door Game' as title"),
            DB::raw("ROUND(($rowExpr),2) as percentage"),
            DB::raw("'' as score_text"),
            DB::raw('DATE_FORMAT(dr.created_at, "%Y-%m-%d %H:%i") as attempted_at'),
        ]);
}

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => (int) $student->id,
                    'uuid' => (string) $student->uuid,
                    'name' => (string) $student->name,
                    'email' => (string) $student->email,
                    'phone_number' => (string) ($student->phone_number ?? ''),
                    'folder_name' => (string) ($folder ?? ''),
                ],
                'quiz_attempts' => $quizAttempts,
                'bubble_attempts' => $bubbleAttempts,
                'door_attempts' => $doorAttempts,
            ]
        ], 200);
    }
}
