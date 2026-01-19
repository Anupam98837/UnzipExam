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

    // Search
    $qText = trim((string) $request->query('q', ''));

    // Filters
    $quizId        = $request->query('quiz_id');
    $quizUuid      = trim((string) $request->query('quiz_uuid', ''));
    $studentId     = $request->query('student_id');
    $studentEmail  = trim((string) $request->query('student_email', ''));
    $attemptUuid   = trim((string) $request->query('attempt_uuid', ''));
    $attemptStatus = trim((string) $request->query('attempt_status', ''));

    $publish = $request->query('publish_to_student', null);

    // convenience alias: only_published=1 means publish_to_student=1
    $onlyPublished = $request->query('only_published', null);
    if ($onlyPublished !== null && in_array((string) $onlyPublished, ['0', '1'], true)) {
        $publish = (string) $onlyPublished;
    }

    $minPct   = $request->query('min_percentage', null);
    $maxPct   = $request->query('max_percentage', null);
    $minMarks = $request->query('min_marks', null);
    $maxMarks = $request->query('max_marks', null);

    $from = trim((string) $request->query('from', '')); // YYYY-MM-DD
    $to   = trim((string) $request->query('to', ''));

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

    // Base query
    $query = DB::table('quizz_results as r')
        ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
        ->join('quizz as qz', 'qz.id', '=', 'r.quiz_id')
        ->join('users as u', 'u.id', '=', 'r.user_id')

        // ✅ NEW: join user_folders via users.user_folder_id
        ->leftJoin('user_folders as uf', 'uf.id', '=', 'u.user_folder_id');

    // Soft delete guards (only if columns exist)
    $query->whereNull('u.deleted_at')
          ->whereNull('qz.deleted_at');

    // Search
    if ($qText !== '') {
        $query->where(function ($w) use ($qText) {
            $w->where('u.name', 'like', "%{$qText}%")
              ->orWhere('u.email', 'like', "%{$qText}%")
              ->orWhere('qz.quiz_name', 'like', "%{$qText}%");
        });
    }

    // Filters
    if ($quizId !== null && is_numeric($quizId))         $query->where('r.quiz_id', (int) $quizId);
    if ($quizUuid !== '')                                $query->where('qz.uuid', $quizUuid);
    if ($studentId !== null && is_numeric($studentId))   $query->where('r.user_id', (int) $studentId);
    if ($studentEmail !== '')                            $query->where('u.email', $studentEmail);
    if ($attemptUuid !== '')                             $query->where('a.uuid', $attemptUuid);
    if ($attemptStatus !== '')                           $query->where('a.status', $attemptStatus);

    if ($publish !== null && in_array((string) $publish, ['0', '1'], true)) {
        $query->where('r.publish_to_student', (int) $publish);
    }

    if ($minPct !== null && is_numeric($minPct))         $query->where('r.percentage', '>=', (float) $minPct);
    if ($maxPct !== null && is_numeric($maxPct))         $query->where('r.percentage', '<=', (float) $maxPct);
    if ($minMarks !== null && is_numeric($minMarks))     $query->where('r.marks_obtained', '>=', (int) $minMarks);
    if ($maxMarks !== null && is_numeric($maxMarks))     $query->where('r.marks_obtained', '<=', (int) $maxMarks);

    // Date range on result created_at
    if ($from !== '') {
        try { $query->where('r.created_at', '>=', Carbon::parse($from)->startOfDay()); } catch (\Throwable $e) {}
    }
    if ($to !== '') {
        try { $query->where('r.created_at', '<=', Carbon::parse($to)->endOfDay()); } catch (\Throwable $e) {}
    }

    /**
     * ✅ IMPORTANT FIX:
     * Clone the filtered base query BEFORE doing select()/order/limit,
     * so aggregate query doesn't mix non-aggregate selected columns.
     */
    $base = clone $query;

    // Total count (use DISTINCT for safety if joins ever duplicate r.id)
    $total = (clone $base)->distinct('r.id')->count('r.id');

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

            // ✅ NEW: folder info
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
                'id'           => (int) $r->student_id,
                'name'         => (string) ($r->student_name ?? ''),
                'email'        => (string) ($r->student_email ?? ''),

                // ✅ NEW: user folder name (from user_folders)
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
            'quiz_id'            => $quizId,
            'quiz_uuid'          => $quizUuid,
            'student_id'         => $studentId,
            'student_email'      => $studentEmail,
            'attempt_uuid'       => $attemptUuid,
            'attempt_status'     => $attemptStatus,
            'publish_to_student' => $publish,
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
}
