<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuizzResultController extends Controller
{
    /**
     * GET /api/quizz/result
     *
     * Query params (optional):
     *  - quiz_id, quiz_uuid, student_id, student_email, attempt_uuid, attempt_status
     *  - q, from, to
     *  - publish_to_student (0/1)
     *  - only_published (0/1)  // alias convenience
     *  - min_percentage, max_percentage, min_marks, max_marks
     *  - sort (-result_created_at, percentage, marks_obtained, student_name, quiz_name, attempt_number)
     *  - page, per_page
     */
 public function index(Request $request)
{
    // Pagination
    $page    = max(1, (int) $request->query('page', 1));
    $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

    // ========= Helpers (IMPORTANT) =========
    $clean = function ($v) {
        if ($v === null) return null;

        if (is_string($v)) {
            $v = trim($v);
            if ($v === '') return null;

            $low = strtolower($v);
            if (in_array($low, ['all', 'any', 'null', 'undefined', 'none'], true)) return null;
            return $v;
        }

        // numeric/bool stays
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

        // Accept 0/1
        if (in_array((string)$v, ['0', '1'], true)) return (int)$v;

        // Accept true/false yes/no
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

    $toInt = function ($v) use ($clean) {
        $v = $clean($v);
        if ($v === null) return null;
        if (!is_numeric($v)) return null;
        return (int)$v;
    };

    // ========= Search =========
    $qText = $clean($request->query('q', ''));

    // ========= Filters (now supports multi-values) =========
    $quizIds       = $toIntList($request->query('quiz_id'));           // supports 1,2 or []
    $quizUuids     = $toStrList($request->query('quiz_uuid'));         // supports csv/array
    $studentIds    = $toIntList($request->query('student_id'));
    $studentEmails = $toStrList($request->query('student_email'));
    $attemptUuids  = $toStrList($request->query('attempt_uuid'));
    $attemptStats  = $toStrList($request->query('attempt_status'));

    // ✅ ✅ ✅ FIX ADDED: folder filters (ID + Name)
    $folderIds     = $toIntList($request->query('folder_id'));         // supports csv/array
    $folderNames   = $toStrList($request->query('folder_name'));       // supports csv/array

    // publish filter + alias only_published
    $publish = $request->query('publish_to_student', null);
    $onlyPublished = $request->query('only_published', null);
    if ($onlyPublished !== null) {
        $publish = $onlyPublished; // override
    }
    $publish01 = $toBool01($publish);

    // ranges
    $minPct   = $toFloat($request->query('min_percentage'));
    $maxPct   = $toFloat($request->query('max_percentage'));
    $minMarks = $toInt($request->query('min_marks'));
    $maxMarks = $toInt($request->query('max_marks'));

    // dates
    $from = $clean($request->query('from', '')); // YYYY-MM-DD
    $to   = $clean($request->query('to', ''));

    // Sorting
    $sort = (string) $request->query('sort', '-result_created_at');
    $dir  = 'asc';
    $col  = $sort;

    if (substr($sort, 0, 1) === '-') {
        $dir = 'desc';
        $col = ltrim($sort, '-');
    }

    $sortMap = [
        'result_created_at' => 'r.created_at',
        'percentage'        => 'r.percentage',
        'marks_obtained'    => 'r.marks_obtained',
        'total_marks'       => 'r.total_marks',
        'attempt_number'    => 'r.attempt_number',
        'student_name'      => 'u.name',
        'quiz_name'         => 'qz.quiz_name',
    ];

    if (!isset($sortMap[$col])) {
        $col = 'result_created_at';
        $dir = 'desc';
    }
    $orderByCol = $sortMap[$col];

    // ========= Base Query =========
    $query = DB::table('quizz_results as r')
        ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
        ->join('quizz as qz', 'qz.id', '=', 'r.quiz_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')

        // ✅ ✅ ✅ FIX: folder join + deleted_at safe
        ->leftJoin('user_folders as uf', function ($j) {
            $j->on('uf.id', '=', 'u.user_folder_id')
              ->whereNull('uf.deleted_at');
        });

    // Soft delete guards
    $query->whereNull('u.deleted_at')
          ->whereNull('qz.deleted_at');

    // Search
    if ($qText !== null) {
        $query->where(function ($w) use ($qText) {
            $w->where('u.name', 'like', "%{$qText}%")
              ->orWhere('u.email', 'like', "%{$qText}%")
              ->orWhere('qz.quiz_name', 'like', "%{$qText}%");
        });
    }

    // ========= Apply Filters (ALL together = AND) =========
    if (!empty($quizIds))       $query->whereIn('r.quiz_id', $quizIds);
    if (!empty($quizUuids))     $query->whereIn('qz.uuid', $quizUuids);
    if (!empty($studentIds))    $query->whereIn('r.user_id', $studentIds);

    // ✅ ✅ ✅ FIX: email filter should work with partial matching + multi terms
    if (!empty($studentEmails)) {
        $query->where(function ($w) use ($studentEmails) {
            foreach ($studentEmails as $t) {
                $w->orWhere('u.email', 'like', "%{$t}%");
            }
        });
    }

    if (!empty($attemptUuids))  $query->whereIn('a.uuid', $attemptUuids);
    if (!empty($attemptStats))  $query->whereIn('a.status', $attemptStats);

    // ✅ ✅ ✅ FIX: folder_id filter
    if (!empty($folderIds)) {
        $query->where(function ($w) use ($folderIds) {
            $w->whereIn('u.user_folder_id', $folderIds)
              ->orWhereIn('uf.id', $folderIds);
        });
    }

    // ✅ ✅ ✅ FIX: folder_name filter (LIKE multi)
    if (!empty($folderNames)) {
        $query->where(function ($w) use ($folderNames) {
            foreach ($folderNames as $t) {
                $w->orWhere('uf.title', 'like', "%{$t}%");
            }
        });
    }

    if ($publish01 !== null) {
        $query->where('r.publish_to_student', $publish01);
    }

    // ranges (with whereBetween)
    if ($minPct !== null || $maxPct !== null) {
        $min = ($minPct !== null) ? $minPct : 0;
        $max = ($maxPct !== null) ? $maxPct : 100;
        if ($min > $max) { $tmp = $min; $min = $max; $max = $tmp; }
        $query->whereBetween('r.percentage', [$min, $max]);
    }

    if ($minMarks !== null || $maxMarks !== null) {
        $min = ($minMarks !== null) ? $minMarks : 0;
        $max = ($maxMarks !== null) ? $maxMarks : PHP_INT_MAX;
        if ($min > $max) { $tmp = $min; $min = $max; $max = $tmp; }
        $query->whereBetween('r.marks_obtained', [$min, $max]);
    }

    // Date range on result created_at
    if ($from !== null && $to !== null) {
        try {
            $query->whereBetween('r.created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);
        } catch (\Throwable $e) {}
    } else {
        if ($from !== null) {
            try { $query->where('r.created_at', '>=', Carbon::parse($from)->startOfDay()); } catch (\Throwable $e) {}
        }
        if ($to !== null) {
            try { $query->where('r.created_at', '<=', Carbon::parse($to)->endOfDay()); } catch (\Throwable $e) {}
        }
    }

    /**
     * ✅ FIX:
     * Clone the filtered base query BEFORE select/order/limit
     */
    $base = clone $query;

    // Total count
    $total = (clone $base)->distinct()->count('r.id');

    // Fetch rows
    $rows = (clone $base)
        ->orderBy($orderByCol, $dir)
        ->orderBy('r.id', 'desc')
        ->offset(($page - 1) * $perPage)
        ->limit($perPage)
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.quiz_id',
            'r.attempt_id',
            'r.user_id as student_id',
            'r.marks_obtained',
            'r.total_marks',
            'r.percentage',
            'r.attempt_number',
            'r.publish_to_student',
            'r.created_at as result_created_at',

            'a.uuid as attempt_uuid',
            'a.status as attempt_status',
            'a.started_at',
            'a.finished_at',
            'a.total_time_sec',
            'a.server_deadline_at',

            'qz.uuid as quiz_uuid',
            'qz.quiz_name',
            'qz.quiz_description',
            'qz.total_time',
            'qz.result_set_up_type',
            'qz.result_release_date',

            'u.name as student_name',
            'u.email as student_email',

            'u.user_folder_id',
            'uf.title as user_folder_name',
        ])
        ->get();

    // time_used_sec map
    $timeMap = [];
    if ($rows->count() > 0) {
        try {
            $timeMap = DB::table('quizz_attempt_answers')
                ->whereIn('attempt_id', $rows->pluck('attempt_id'))
                ->groupBy('attempt_id')
                ->select('attempt_id', DB::raw('SUM(time_spent_sec) as time_used_sec'))
                ->pluck('time_used_sec', 'attempt_id');
        } catch (\Throwable $e) {
            $timeMap = [];
        }
    }

    $data = $rows->map(function ($r) use ($timeMap) {
        $timeUsed = isset($timeMap[$r->attempt_id]) ? (int) $timeMap[$r->attempt_id] : 0;

        return [
            'quiz' => [
                'id'          => (int) $r->quiz_id,
                'uuid'        => (string) $r->quiz_uuid,
                'name'        => (string) ($r->quiz_name ?? ''),
                'description' => (string) ($r->quiz_description ?? ''),
                'total_time'  => (int) ($r->total_time ?? 0),
                'result_set_up_type'  => (string) ($r->result_set_up_type ?? ''),
                'result_release_date' => $r->result_release_date
                    ? Carbon::parse($r->result_release_date)->toDateTimeString()
                    : null,
            ],
            'student' => [
                'id'              => (int) $r->student_id,
                'name'            => (string) ($r->student_name ?? ''),
                'email'           => (string) ($r->student_email ?? ''),
                'user_folder_id'   => $r->user_folder_id !== null ? (int) $r->user_folder_id : null,
                'user_folder_name' => (string) ($r->user_folder_name ?? ''),
            ],
            'attempt' => [
                'id'                 => (int) $r->attempt_id,
                'uuid'               => (string) $r->attempt_uuid,
                'status'             => (string) $r->attempt_status,
                'started_at'         => $r->started_at ? Carbon::parse($r->started_at)->toDateTimeString() : null,
                'finished_at'        => $r->finished_at ? Carbon::parse($r->finished_at)->toDateTimeString() : null,
                'server_deadline_at' => $r->server_deadline_at ? Carbon::parse($r->server_deadline_at)->toDateTimeString() : null,
                'total_time_sec'     => (int) ($r->total_time_sec ?? 0),
                'time_used_sec'      => $timeUsed,
            ],
            'result' => [
                'id'                 => (int) $r->result_id,
                'uuid'               => (string) ($r->result_uuid ?? ''),
                'marks_obtained'     => (int) $r->marks_obtained,
                'total_marks'        => (int) $r->total_marks,
                'percentage'         => (float) ($r->percentage ?? 0),
                'attempt_number'     => (int) ($r->attempt_number ?? 0),
                'publish_to_student' => (int) ($r->publish_to_student ?? 0),
                'created_at'         => $r->result_created_at ? Carbon::parse($r->result_created_at)->toDateTimeString() : null,
            ],
        ];
    })->values();

    // Aggregate stats
    $agg = (clone $base)->selectRaw('
        COUNT(DISTINCT r.id) as total_results,
        AVG(r.percentage) as avg_percentage,
        MAX(r.percentage) as max_percentage,
        MIN(r.percentage) as min_percentage
    ')->first();

    return response()->json([
        'success' => true,
        'filters' => [
            'q'                  => $qText,
            'quiz_id'            => $quizIds,
            'quiz_uuid'          => $quizUuids,
            'student_id'         => $studentIds,
            'student_email'      => $studentEmails,
            'attempt_uuid'       => $attemptUuids,
            'attempt_status'     => $attemptStats,

            // ✅ ✅ ✅ FIX: return folder filters too
            'folder_id'          => $folderIds,
            'folder_name'        => $folderNames,

            'publish_to_student' => $publish01,
            'only_published'     => $onlyPublished,
            'min_percentage'     => $minPct,
            'max_percentage'     => $maxPct,
            'min_marks'          => $minMarks,
            'max_marks'          => $maxMarks,
            'from'               => $from,
            'to'                 => $to,
            'sort'               => $sort,
        ],
        'stats' => [
            'total_results'  => (int) ($agg->total_results ?? 0),
            'avg_percentage' => $agg->avg_percentage !== null ? round((float) $agg->avg_percentage, 2) : null,
            'max_percentage' => $agg->max_percentage !== null ? (float) $agg->max_percentage : null,
            'min_percentage' => $agg->min_percentage !== null ? (float) $agg->min_percentage : null,
        ],
        'data' => $data,
        'pagination' => [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => (int) $total,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ],
    ], 200);
}

    /**
     * ✅ PATCH /api/quizz/result/{resultId}/publish
     *
     * Body (JSON):
     *  - publish_to_student: 1 or 0  (default 1)
     *
     * Example:
     *  { "publish_to_student": 1 }
     */
    public function publishToStudent(Request $request, $resultId)
    {
        $publish = $request->input('publish_to_student', 1);

        if (!in_array((string) $publish, ['0', '1'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'publish_to_student must be 0 or 1',
            ], 422);
        }

        $resultId = (int) $resultId;

        $row = DB::table('quizz_results')
            ->where('id', $resultId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Result not found',
            ], 404);
        }

        DB::table('quizz_results')
            ->where('id', $resultId)
            ->update([
                'publish_to_student' => (int) $publish,
                'updated_at'         => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => ((int) $publish === 1)
                ? 'Result published to student'
                : 'Result unpublished from student',
            'result' => [
                'id' => $resultId,
                'publish_to_student' => (int) $publish,
            ],
        ], 200);
    }


    /**
     * ✅ POST /api/quizz/result/publish/bulk
     *
     * Bulk Publish/Unpublish results.
     *
     * Body (JSON) Options:
     *
     * 1) By result_ids:
     * {
     *   "publish_to_student": 1,
     *   "result_ids": [1,2,3,4]
     * }
     *
     * 2) By quiz filter:
     * {
     *   "publish_to_student": 1,
     *   "quiz_id": 10
     * }
     *
     * 3) By quiz_uuid filter:
     * {
     *   "publish_to_student": 1,
     *   "quiz_uuid": "xxxx-xxxx"
     * }
     *
     * Optional extra filters:
     *  - attempt_status: "completed"
     *  - from: "YYYY-MM-DD"
     *  - to: "YYYY-MM-DD"
     */
    public function bulkPublishToStudent(Request $request)
    {
        $publish = $request->input('publish_to_student', 1);

        if (!in_array((string) $publish, ['0', '1'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'publish_to_student must be 0 or 1',
            ], 422);
        }

        $resultIds     = $request->input('result_ids', []);
        $quizId        = $request->input('quiz_id', null);
        $quizUuid      = trim((string) $request->input('quiz_uuid', ''));
        $attemptStatus = trim((string) $request->input('attempt_status', ''));

        $from = trim((string) $request->input('from', ''));
        $to   = trim((string) $request->input('to', ''));

        // ✅ Safety: must provide at least one selector
        $hasIds   = is_array($resultIds) && count($resultIds) > 0;
        $hasQuiz  = ($quizId !== null && is_numeric($quizId)) || ($quizUuid !== '');
        $hasOther = ($attemptStatus !== '') || ($from !== '') || ($to !== '');

        if (!$hasIds && !$hasQuiz && !$hasOther) {
            return response()->json([
                'success' => false,
                'message' => 'Provide result_ids OR quiz_id OR quiz_uuid OR filters (attempt_status/from/to) for bulk publish.',
            ], 422);
        }

        // Build bulk update query
        $q = DB::table('quizz_results as r')
            ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
            ->join('quizz as qz', 'qz.id', '=', 'r.quiz_id');

        // Filter by ids
        if ($hasIds) {
            $cleanIds = collect($resultIds)
                ->filter(fn($x) => is_numeric($x))
                ->map(fn($x) => (int) $x)
                ->values()
                ->all();

            if (count($cleanIds) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'result_ids must contain numeric ids',
                ], 422);
            }

            $q->whereIn('r.id', $cleanIds);
        }

        // Filter by quiz
        if ($quizId !== null && is_numeric($quizId)) {
            $q->where('r.quiz_id', (int) $quizId);
        }
        if ($quizUuid !== '') {
            $q->where('qz.uuid', $quizUuid);
        }

        // Filter by attempt status
        if ($attemptStatus !== '') {
            $q->where('a.status', $attemptStatus);
        }

        // Date range (result created_at)
        if ($from !== '') {
            try { $q->where('r.created_at', '>=', Carbon::parse($from)->startOfDay()); } catch (\Throwable $e) {}
        }
        if ($to !== '') {
            try { $q->where('r.created_at', '<=', Carbon::parse($to)->endOfDay()); } catch (\Throwable $e) {}
        }

        // Get affected ids (for response)
        $affectedIds = (clone $q)->distinct('r.id')->pluck('r.id')->values();

        if ($affectedIds->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No results found for given bulk publish filters.',
                'updated' => [
                    'count' => 0,
                    'ids'   => [],
                ],
            ], 404);
        }

        // ✅ Update in chunks (safe for big lists)
        $updatedCount = 0;
        DB::beginTransaction();
        try {
            $affectedIds->chunk(500)->each(function ($chunk) use ($publish, &$updatedCount) {
                $updatedCount += DB::table('quizz_results')
                    ->whereIn('id', $chunk->all())
                    ->update([
                        'publish_to_student' => (int) $publish,
                        'updated_at'         => now(),
                    ]);
            });

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk publish failed',
                'error'   => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => ((int) $publish === 1)
                ? 'Bulk publish to students completed'
                : 'Bulk unpublish from students completed',
            'updated' => [
                'count' => (int) $updatedCount,
                'ids'   => $affectedIds,
                'publish_to_student' => (int) $publish,
            ],
        ], 200);
    }
   public function export(Request $request)
{
    try {
        // ========= Helpers (same as index) =========
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

        $toInt = function ($v) use ($clean) {
            $v = $clean($v);
            if ($v === null) return null;
            if (!is_numeric($v)) return null;
            return (int)$v;
        };

        // ========= Safe table/column checks =========
        $hasUserDeletedAt = false;
        $hasQuizDeletedAt = false;

        $hasFolderTable = false;
        $hasFolderDeletedAt = false;

        $hasPhoneNumber = false;
        $hasPhoneNo = false;

        try {
            $hasUserDeletedAt = Schema::hasColumn('users', 'deleted_at');
            $hasQuizDeletedAt = Schema::hasColumn('quizz', 'deleted_at');

            $hasFolderTable = Schema::hasTable('user_folders');
            if ($hasFolderTable) {
                $hasFolderDeletedAt = Schema::hasColumn('user_folders', 'deleted_at');
            }

            $hasPhoneNumber = Schema::hasColumn('users', 'phone_number');
            $hasPhoneNo     = Schema::hasColumn('users', 'phone_no');
        } catch (\Throwable $e) {}

        // ========= Search =========
        $qText = $clean($request->query('q', ''));

        // ========= Filters (same as index) =========
        $quizIds       = $toIntList($request->query('quiz_id'));
        $quizUuids     = $toStrList($request->query('quiz_uuid'));
        $studentIds    = $toIntList($request->query('student_id'));
        $studentEmails = $toStrList($request->query('student_email'));
        $attemptUuids  = $toStrList($request->query('attempt_uuid'));
        $attemptStats  = $toStrList($request->query('attempt_status'));

        // publish filter + alias only_published
        $publish = $request->query('publish_to_student', null);
        $onlyPublished = $request->query('only_published', null);
        if ($onlyPublished !== null) {
            $publish = $onlyPublished;
        }
        $publish01 = $toBool01($publish);

        // ranges
        $minPct   = $toFloat($request->query('min_percentage'));
        $maxPct   = $toFloat($request->query('max_percentage'));
        $minMarks = $toInt($request->query('min_marks'));
        $maxMarks = $toInt($request->query('max_marks'));

        // dates
        $from = $clean($request->query('from', ''));
        $to   = $clean($request->query('to', ''));

        // Sorting
        $sort = (string) $request->query('sort', '-result_created_at');
        $dir  = 'asc';
        $col  = $sort;

        if (substr($sort, 0, 1) === '-') {
            $dir = 'desc';
            $col = ltrim($sort, '-');
        }

        $sortMap = [
            'result_created_at' => 'r.created_at',
            'percentage'        => 'r.percentage',
            'marks_obtained'    => 'r.marks_obtained',
            'total_marks'       => 'r.total_marks',
            'attempt_number'    => 'r.attempt_number',
            'student_name'      => 'u.name',
            'quiz_name'         => 'qz.quiz_name',
        ];

        if (!isset($sortMap[$col])) {
            $col = 'result_created_at';
            $dir = 'desc';
        }
        $orderByCol = $sortMap[$col];

        // ========= Base Query (same as index) =========
        $query = DB::table('quizz_results as r')
            ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
            ->join('quizz as qz', 'qz.id', '=', 'r.quiz_id')
            ->join('users as u', 'u.id', '=', 'r.user_id');

        // ✅ Folder join safe
        if ($hasFolderTable) {
            $query->leftJoin('user_folders as uf', function ($j) use ($hasFolderDeletedAt) {
                $j->on('uf.id', '=', 'u.user_folder_id');
                if ($hasFolderDeletedAt) $j->whereNull('uf.deleted_at');
            });
        }

        // ✅ Soft delete guards (SAFE)
        if ($hasUserDeletedAt) $query->whereNull('u.deleted_at');
        if ($hasQuizDeletedAt) $query->whereNull('qz.deleted_at');

        // Search
        if ($qText !== null) {
            $query->where(function ($w) use ($qText) {
                $w->where('u.name', 'like', "%{$qText}%")
                  ->orWhere('u.email', 'like', "%{$qText}%")
                  ->orWhere('qz.quiz_name', 'like', "%{$qText}%");
            });
        }

        // Apply all filters
        if (!empty($quizIds))       $query->whereIn('r.quiz_id', $quizIds);
        if (!empty($quizUuids))     $query->whereIn('qz.uuid', $quizUuids);
        if (!empty($studentIds))    $query->whereIn('r.user_id', $studentIds);

        // ✅ email terms should be OR like (more flexible)
        if (!empty($studentEmails)) {
            $query->where(function ($w) use ($studentEmails) {
                foreach ($studentEmails as $t) {
                    $w->orWhere('u.email', 'like', "%{$t}%");
                }
            });
        }

        if (!empty($attemptUuids))  $query->whereIn('a.uuid', $attemptUuids);
        if (!empty($attemptStats))  $query->whereIn('a.status', $attemptStats);

        if ($publish01 !== null) {
            $query->where('r.publish_to_student', $publish01);
        }

        // ranges
        if ($minPct !== null || $maxPct !== null) {
            $min = ($minPct !== null) ? $minPct : 0;
            $max = ($maxPct !== null) ? $maxPct : 100;
            if ($min > $max) { $tmp = $min; $min = $max; $max = $tmp; }
            $query->whereBetween('r.percentage', [$min, $max]);
        }

        if ($minMarks !== null || $maxMarks !== null) {
            $min = ($minMarks !== null) ? $minMarks : 0;
            $max = ($maxMarks !== null) ? $maxMarks : PHP_INT_MAX;
            if ($min > $max) { $tmp = $min; $min = $max; $max = $tmp; }
            $query->whereBetween('r.marks_obtained', [$min, $max]);
        }

        // Date range
        if ($from !== null && $to !== null) {
            try {
                $query->whereBetween('r.created_at', [
                    Carbon::parse($from)->startOfDay(),
                    Carbon::parse($to)->endOfDay(),
                ]);
            } catch (\Throwable $e) {}
        } else {
            if ($from !== null) {
                try { $query->where('r.created_at', '>=', Carbon::parse($from)->startOfDay()); } catch (\Throwable $e) {}
            }
            if ($to !== null) {
                try { $query->where('r.created_at', '<=', Carbon::parse($to)->endOfDay()); } catch (\Throwable $e) {}
            }
        }

        // ========= Select Fields (SAFE PHONE + SAFE FOLDER) =========
        $phoneSelect = DB::raw('NULL as phone_no');
        if ($hasPhoneNumber) $phoneSelect = DB::raw('u.phone_number as phone_no');
        else if ($hasPhoneNo) $phoneSelect = DB::raw('u.phone_no as phone_no');

        $folderSelect = DB::raw('NULL as user_folder_name');
        if ($hasFolderTable) $folderSelect = DB::raw('uf.title as user_folder_name');

        // ✅ quick existence check (cheap)
        if (!$query->clone()->limit(1)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No results found to export',
            ], 404);
        }

        // ========= Generate CSV (STREAM) =========
        $filename = 'quiz_results_' . Carbon::now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query, $orderByCol, $dir, $phoneSelect, $folderSelect) {
            $file = fopen('php://output', 'w');

            // BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Headers
            fputcsv($file, [
                'Student Name',
                'Phone No',
                'Email',
                'User Folder Name',
                'Quiz Name',
                'Marks Obtained',
                'Percentage (%)',
                'Attempt Number',
            ]);

            // ✅ Chunked export (memory safe)
            $query
                ->orderBy($orderByCol, $dir)
                ->orderBy('r.id', 'desc')
                ->select([
                    'r.id',
                    'r.marks_obtained',
                    'r.percentage',
                    'r.attempt_number',
                    'qz.quiz_name',
                    'u.name as student_name',
                    'u.email as student_email',
                    $phoneSelect,
                    $folderSelect,
                ])
                ->chunkById(500, function ($rows) use ($file) {
                    foreach ($rows as $r) {
                        fputcsv($file, [
                            $r->student_name ?? '',
                            $r->phone_no ?? '',
                            $r->student_email ?? '',
                            $r->user_folder_name ?? '',
                            $r->quiz_name ?? '',
                            (int)($r->marks_obtained ?? 0),
                            number_format((float)($r->percentage ?? 0), 2),
                            (int)($r->attempt_number ?? 0),
                        ]);
                    }
                }, 'r.id');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error while exporting results',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
