<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /* =========================================================
     | Helpers
     * ========================================================= */

    /**
     * Convenience helper to read actor data attached by CheckRole.
     */
    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    /**
     * Simple role gate (same style as QuizzController).
     */
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

    /**
     * Calculate date range based on period.
     *
     * Supported: 7d, 30d, 90d, 1y
     */
    private function getDateRange(string $period): array
    {
        $end = Carbon::now()->endOfDay();

        switch ($period) {
            case '7d':
                $start = $end->copy()->subDays(7);
                break;
            case '90d':
                $start = $end->copy()->subDays(90);
                break;
            case '1y':
                $start = $end->copy()->subYear();
                break;
            case '30d':
            default:
                $start  = $end->copy()->subDays(30);
                $period = '30d';
                break;
        }

        return [
            'start'  => $start->startOfDay(),
            'end'    => $end,
            'period' => $period,
        ];
    }

    /* =========================================================
     | Admin dashboard (summary of whole system)
     | GET /api/dashboard/admin
     * ========================================================= */

    public function adminDashboard(Request $request)
    {
        if ($resp = $this->requireRole($request, ['admin','super_admin'])) {
            return $resp;
        }

        try {
            $period    = (string) $request->query('period', '30d'); // 7d, 30d, 90d, 1y
            $range     = $this->getDateRange($period);
            $startDate = $range['start'];
            $endDate   = $range['end'];
            $period    = $range['period'];
            $today     = Carbon::today();

            /* ---------- Core counts ---------- */
            $summaryCounts = [
                // Users
                'total_users'     => DB::table('users')->whereNull('deleted_at')->count(),
                'total_students'  => DB::table('users')->whereNull('deleted_at')->where('role', 'student')->count(),
                'total_admins'    => DB::table('users')->whereNull('deleted_at')->whereIn('role', ['admin', 'super_admin'])->count(),
                'total_examiners' => DB::table('users')->whereNull('deleted_at')->where('role', 'examiner')->count(),

                // Quizzes
                'total_quizzes'    => DB::table('quizz')->whereNull('deleted_at')->count(),
                'active_quizzes'   => DB::table('quizz')->whereNull('deleted_at')->where('status', 'active')->count(),
                'archived_quizzes' => DB::table('quizz')->whereNull('deleted_at')->where('status', 'archived')->count(),

                // Questions
                'total_questions' => DB::table('quizz_questions')->count(),

                // Attempts & results
                'total_attempts'     => DB::table('quizz_attempts')->whereNull('deleted_at')->count(),
                'completed_attempts' => DB::table('quizz_attempts')
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['submitted','auto_submitted'])
                    ->count(),
                'total_results'      => DB::table('quizz_results')->count(),
            ];

            /* ---------- Quick stats for today ---------- */
            $quickStats = [
                'today_new_users' => DB::table('users')
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $today)
                    ->count(),

                'today_quizzes_created' => DB::table('quizz')
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $today)
                    ->count(),

                'today_attempts_started' => DB::table('quizz_attempts')
                    ->whereNull('deleted_at')
                    ->whereDate('created_at', $today)
                    ->count(),

                'today_attempts_completed' => DB::table('quizz_attempts')
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['submitted','auto_submitted'])
                    ->whereDate('finished_at', $today)
                    ->count(),

                // Rough online users based on token last_used_at
                'active_sessions' => DB::table('personal_access_tokens')
                    ->where('last_used_at', '>=', $today)
                    ->distinct('tokenable_id')
                    ->count('tokenable_id'),
            ];

            /* ---------- Attempt status distribution ---------- */
            $attemptStatusStats = DB::table('quizz_attempts')
                ->select('status', DB::raw('COUNT(*) as count'))
                ->whereNull('deleted_at')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($row) {
                    return [
                        (string) $row->status => (int) $row->count,
                    ];
                })
                ->toArray();

            /* ---------- Question difficulty distribution ---------- */
            $difficultyStats = DB::table('quizz_questions')
                ->select('question_difficulty', DB::raw('COUNT(*) as count'))
                ->groupBy('question_difficulty')
                ->get()
                ->mapWithKeys(function ($row) {
                    $key = $row->question_difficulty ?: 'unknown';
                    return [(string) $key => (int) $row->count];
                })
                ->toArray();

            /* ---------- Quizzes created over time ---------- */
            $quizzesOverTime = DB::table('quizz')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            /* ---------- Attempts over time ---------- */
            $attemptsOverTime = DB::table('quizz_attempts')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNull('deleted_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            /* ---------- Average score over time ---------- */
            $scoresOverTime = DB::table('quizz_results')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('AVG(percentage) as avg_percentage')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            /* ---------- Recent admin activity timeline ---------- */
            $recentActivities = DB::table('user_data_activity_log')
                ->select('activity', 'module', 'performed_by', 'performed_by_role', 'log_note', 'created_at')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            /* ---------- Top quizzes by attempts ---------- */
            $topQuizzes = DB::table('quizz_attempts as qa')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->select(
                    'q.id',
                    'q.uuid',
                    'q.quiz_name',
                    DB::raw('COUNT(qa.id) as total_attempts'),
                    DB::raw('SUM(CASE WHEN qa.status IN ("submitted","auto_submitted") THEN 1 ELSE 0 END) as completed_attempts')
                )
                ->whereNull('qa.deleted_at')
                ->whereNull('q.deleted_at')
                ->whereBetween('qa.created_at', [$startDate, $endDate])
                ->groupBy('q.id', 'q.uuid', 'q.quiz_name')
                ->orderByDesc('total_attempts')
                ->limit(5)
                ->get();

            /* ---------- Top students by activity/performance ---------- */
            $topStudents = DB::table('quizz_results as qr')
                ->join('users as u', 'u.id', '=', 'qr.user_id')
                ->select(
                    'u.id',
                    'u.uuid',
                    'u.name',
                    'u.email',
                    DB::raw('COUNT(qr.id) as attempts'),
                    DB::raw('AVG(qr.percentage) as avg_percentage')
                )
                ->groupBy('u.id', 'u.uuid', 'u.name', 'u.email')
                ->whereBetween('qr.created_at', [$startDate, $endDate])
                ->orderByDesc('attempts')
                ->limit(5)
                ->get();

            /* ---------- Notifications summary ---------- */
            $notificationsSummary = [
                'total_active' => DB::table('notifications')
                    ->where('status', 'active')
                    ->count(),
                'latest' => DB::table('notifications')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(['id','title','message','priority','status','created_at']),
            ];

            /* ---------- System health ---------- */
            $failedJobs = 0;
            try {
                if (Schema::hasTable('failed_jobs')) {
                    $failedJobs = DB::table('failed_jobs')
                        ->whereBetween('failed_at', [$startDate, $endDate])
                        ->count();
                }
            } catch (\Throwable $e) {
                $failedJobs = 0;
            }

            $recentErrors = DB::table('user_data_activity_log')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where(function ($q) {
                    $q->where('activity', 'like', '%error%')
                      ->orWhere('activity', 'like', '%fail%')
                      ->orWhere('log_note', 'like', '%error%')
                      ->orWhere('log_note', 'like', '%fail%');
                })
                ->count();

            $systemHealth = [
                'failed_jobs'   => $failedJobs,
                'recent_errors' => $recentErrors,
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Admin dashboard data fetched successfully',
                'data'    => [
                    'summary_counts'              => $summaryCounts,
                    'quick_stats'                 => $quickStats,
                    'attempt_status_distribution' => $attemptStatusStats,
                    'question_difficulty_stats'   => $difficultyStats,
                    'quizzes_over_time'           => $quizzesOverTime,
                    'attempts_over_time'          => $attemptsOverTime,
                    'scores_over_time'            => $scoresOverTime,
                    'recent_activities'           => $recentActivities,
                    'top_quizzes'                 => $topQuizzes,
                    'top_students'                => $topStudents,
                    'notifications'               => $notificationsSummary,
                    'system_health'               => $systemHealth,
                    'date_range' => [
                        'start'  => $startDate->toDateString(),
                        'end'    => $endDate->toDateString(),
                        'period' => $period,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[Dashboard Admin] Failed to build dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch admin dashboard data',
            ], 500);
        }
    }

    /* =========================================================
     | Student dashboard
     | GET /api/dashboard/student
     * ========================================================= */

    public function studentDashboard(Request $request)
    {
        // Allow student; also useful to let admin/super_admin inspect a student's view
        if ($resp = $this->requireRole($request, ['student','admin','super_admin'])) {
            return $resp;
        }

        $actor  = $this->actor($request);
        $userId = (int) ($actor['id'] ?? 0);

        if (!$userId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to resolve user from token',
            ], 403);
        }

        try {
            $period    = (string) $request->query('period', '30d');
            $range     = $this->getDateRange($period);
            $startDate = $range['start'];
            $endDate   = $range['end'];
            $period    = $range['period'];
            $today     = Carbon::today();

            /* ---------- Summary counts for this student ---------- */
            $assignedQuizzesCount = DB::table('user_quiz_assignments')
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->count();

            $totalAttempts = DB::table('quizz_attempts')
                ->whereNull('deleted_at')
                ->where('user_id', $userId)
                ->count();

            $completedAttempts = DB::table('quizz_attempts')
                ->whereNull('deleted_at')
                ->where('user_id', $userId)
                ->whereIn('status', ['submitted','auto_submitted'])
                ->count();

            $resultsTotal = DB::table('quizz_results')
                ->where('user_id', $userId)
                ->count();

            $avgRow = DB::table('quizz_results')
                ->where('user_id', $userId)
                ->select(DB::raw('AVG(percentage) as avg_percentage'))
                ->first();

            $avgPercentage = $avgRow && $avgRow->avg_percentage !== null
                ? round((float) $avgRow->avg_percentage, 2)
                : 0.0;

            $bestRow = DB::table('quizz_results')
                ->where('user_id', $userId)
                ->orderByDesc('percentage')
                ->select('quiz_id','percentage','marks_obtained','total_marks')
                ->first();

            $bestPerformance = $bestRow ? [
                'quiz_id'        => (int) $bestRow->quiz_id,
                'percentage'     => (float) $bestRow->percentage,
                'marks_obtained' => (int) $bestRow->marks_obtained,
                'total_marks'    => (int) $bestRow->total_marks,
            ] : null;

            $summaryCounts = [
                'assigned_quizzes'    => $assignedQuizzesCount,
                'total_attempts'      => $totalAttempts,
                'completed_attempts'  => $completedAttempts,
                'total_results'       => $resultsTotal,
                'average_percentage'  => $avgPercentage,
                'best_performance'    => $bestPerformance,
            ];

            /* ---------- Quick stats for today ---------- */
            $todayAttemptsStarted = DB::table('quizz_attempts')
                ->whereNull('deleted_at')
                ->where('user_id', $userId)
                ->whereDate('created_at', $today)
                ->count();

            $todayAttemptsCompleted = DB::table('quizz_attempts')
                ->whereNull('deleted_at')
                ->where('user_id', $userId)
                ->whereIn('status', ['submitted','auto_submitted'])
                ->whereDate('finished_at', $today)
                ->count();

            $todayTimeSpent = DB::table('quizz_attempt_answers as qaa')
                ->join('quizz_attempts as qa', 'qa.id', '=', 'qaa.attempt_id')
                ->where('qa.user_id', $userId)
                ->whereDate('qaa.answered_at', $today)
                ->sum('qaa.time_spent_sec');

            $quickStats = [
                'today_attempts_started'   => $todayAttemptsStarted,
                'today_attempts_completed' => $todayAttemptsCompleted,
                'today_time_spent_sec'     => (int) $todayTimeSpent,
            ];

            /* ---------- Attempts over time for this student ---------- */
            $attemptsOverTime = DB::table('quizz_attempts')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNull('deleted_at')
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            /* ---------- Scores over time for this student ---------- */
            $scoresOverTime = DB::table('quizz_results')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('AVG(percentage) as avg_percentage')
                )
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            /* ---------- Recent attempts (last 5) ---------- */
            $recentAttempts = DB::table('quizz_attempts as qa')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->leftJoin('quizz_results as qr', 'qr.attempt_id', '=', 'qa.id')
                ->whereNull('qa.deleted_at')
                ->where('qa.user_id', $userId)
                ->orderByDesc('qa.created_at')
                ->limit(5)
                ->get([
                    'qa.id                   as attempt_id',
                    'qa.uuid                 as attempt_uuid',
                    'qa.status               as attempt_status',
                    'qa.started_at           as started_at',
                    'qa.finished_at          as finished_at',
                    'qa.server_deadline_at   as server_deadline_at',
                    'q.id                    as quiz_id',
                    'q.uuid                  as quiz_uuid',
                    'q.quiz_name',
                    'q.total_questions',
                    'q.total_time',
                    'qr.id                   as result_id',
                    'qr.percentage           as result_percentage',
                    'qr.marks_obtained',
                    'qr.total_marks',
                ]);

            /* ---------- Upcoming / active quizzes (assignments not yet completed) ---------- */
            $completedSub = DB::table('quizz_attempts')
                ->select('quiz_id')
                ->where('user_id', $userId)
                ->whereIn('status', ['submitted','auto_submitted'])
                ->groupBy('quiz_id');

            $upcomingQuizzes = DB::table('user_quiz_assignments as uqa')
                ->join('quizz as q', 'q.id', '=', 'uqa.quiz_id')
                ->leftJoinSub($completedSub, 'cq', function ($join) {
                    $join->on('cq.quiz_id', '=', 'q.id');
                })
                ->where('uqa.user_id', $userId)
                ->whereNull('uqa.deleted_at')
                ->where('uqa.status', 'active')
                ->whereNull('q.deleted_at')
                ->where('q.status', 'active')
                ->whereNull('cq.quiz_id')
                ->orderByDesc('uqa.assigned_at')
                ->limit(5)
                ->get([
                    'q.id                as quiz_id',
                    'q.uuid              as quiz_uuid',
                    'q.quiz_name',
                    'q.total_questions',
                    'q.total_time',
                    'q.result_set_up_type',
                    'uqa.assignment_code',
                    'uqa.assigned_at',
                ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Student dashboard data fetched successfully',
                'data'    => [
                    'summary_counts'     => $summaryCounts,
                    'quick_stats'        => $quickStats,
                    'attempts_over_time' => $attemptsOverTime,
                    'scores_over_time'   => $scoresOverTime,
                    'recent_attempts'    => $recentAttempts,
                    'upcoming_quizzes'   => $upcomingQuizzes,
                    'date_range' => [
                        'start'  => $startDate->toDateString(),
                        'end'    => $endDate->toDateString(),
                        'period' => $period,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[Dashboard Student] Failed to build dashboard', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch student dashboard data',
            ], 500);
        }
    }

        /* =========================================================
     | Examiner dashboard
     | GET /api/dashboard/examiner
     * ========================================================= */

    public function examinerDashboard(Request $request)
    {
        // Allow examiner; admin/super_admin can also inspect
        if ($resp = $this->requireRole($request, ['examiner','admin','super_admin'])) {
            return $resp;
        }

        $actor  = $this->actor($request);
        $userId = (int) ($actor['id'] ?? 0);

        if (!$userId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to resolve user from token',
            ], 403);
        }

        try {
            $period    = (string) $request->query('period', '30d'); // 7d, 30d, 90d, 1y
            $range     = $this->getDateRange($period);
            $startDate = $range['start'];
            $endDate   = $range['end'];
            $period    = $range['period'];
            $today     = Carbon::today();

            /*
             * ---------- Summary counts for this examiner ----------
             */

            // Quizzes created by this examiner
            $totalCreatedQuizzes = DB::table('quizz')
                ->whereNull('deleted_at')
                ->where('created_by', $userId)
                ->count();

            $activeCreatedQuizzes = DB::table('quizz')
                ->whereNull('deleted_at')
                ->where('created_by', $userId)
                ->where('status', 'active')
                ->count();

            $archivedCreatedQuizzes = DB::table('quizz')
                ->whereNull('deleted_at')
                ->where('created_by', $userId)
                ->where('status', 'archived')
                ->count();

            // Assignments done by this examiner
            $totalAssignments = DB::table('user_quiz_assignments')
                ->whereNull('deleted_at')
                ->where('assigned_by', $userId)
                ->count();

            $activeAssignments = DB::table('user_quiz_assignments')
                ->whereNull('deleted_at')
                ->where('assigned_by', $userId)
                ->where('status', 'active')
                ->count();

            $uniqueStudentsAssigned = DB::table('user_quiz_assignments')
                ->whereNull('deleted_at')
                ->where('assigned_by', $userId)
                ->distinct('user_id')
                ->count('user_id');

            // Attempts on quizzes created by this examiner
            $baseAttemptsQuery = DB::table('quizz_attempts as qa')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->whereNull('qa.deleted_at')
                ->whereNull('q.deleted_at')
                ->where('q.created_by', $userId);

            $totalAttemptsOnMyQuizzes = (clone $baseAttemptsQuery)->count('qa.id');

            $completedAttemptsOnMyQuizzes = (clone $baseAttemptsQuery)
                ->whereIn('qa.status', ['submitted', 'auto_submitted'])
                ->count('qa.id');

            $uniqueStudentsAttempted = (clone $baseAttemptsQuery)
                ->distinct('qa.user_id')
                ->count('qa.user_id');

            // Average performance across this examiner's quizzes (within range)
            $avgRow = DB::table('quizz_results as qr')
                ->join('quizz_attempts as qa', 'qa.id', '=', 'qr.attempt_id')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->whereNull('qa.deleted_at')
                ->whereNull('q.deleted_at')
                ->where('q.created_by', $userId)
                ->whereBetween('qr.created_at', [$startDate, $endDate])
                ->select(DB::raw('AVG(qr.percentage) as avg_percentage'))
                ->first();

            $avgPercentage = $avgRow && $avgRow->avg_percentage !== null
                ? round((float) $avgRow->avg_percentage, 2)
                : 0.0;

            $summaryCounts = [
                'created_quizzes_total'            => $totalCreatedQuizzes,
                'created_quizzes_active'           => $activeCreatedQuizzes,
                'created_quizzes_archived'         => $archivedCreatedQuizzes,
                'total_assignments'                => $totalAssignments,
                'active_assignments'               => $activeAssignments,
                'students_assigned'                => $uniqueStudentsAssigned,
                'total_attempts_on_my_quizzes'     => $totalAttemptsOnMyQuizzes,
                'completed_attempts_on_my_quizzes' => $completedAttemptsOnMyQuizzes,
                'students_attempted'               => $uniqueStudentsAttempted,
                'average_percentage'               => $avgPercentage,
            ];

            /*
             * ---------- Quick stats for today ----------
             */

            $todayQuizzesCreated = DB::table('quizz')
                ->whereNull('deleted_at')
                ->where('created_by', $userId)
                ->whereDate('created_at', $today)
                ->count();

            $todayAssignmentsCreated = DB::table('user_quiz_assignments')
                ->whereNull('deleted_at')
                ->where('assigned_by', $userId)
                ->whereDate('assigned_at', $today)
                ->count();

            $todayAttemptsOnMyQuizzes = (clone $baseAttemptsQuery)
                ->whereDate('qa.created_at', $today)
                ->count('qa.id');

            $todayCompletedAttemptsOnMyQuizzes = (clone $baseAttemptsQuery)
                ->whereIn('qa.status', ['submitted', 'auto_submitted'])
                ->whereDate('qa.finished_at', $today)
                ->count('qa.id');

            $quickStats = [
                'today_quizzes_created'                 => $todayQuizzesCreated,
                'today_assignments_created'             => $todayAssignmentsCreated,
                'today_attempts_on_my_quizzes'          => $todayAttemptsOnMyQuizzes,
                'today_completed_attempts_on_my_quizzes'=> $todayCompletedAttemptsOnMyQuizzes,
            ];

            /*
             * ---------- Quizzes created over time (for this examiner) ----------
             */
            $quizzesOverTime = DB::table('quizz')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNull('deleted_at')
                ->where('created_by', $userId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            /*
             * ---------- Attempts over time on this examiner's quizzes ----------
             */
            $attemptsOverTime = DB::table('quizz_attempts as qa')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->select(
                    DB::raw('DATE(qa.created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereNull('qa.deleted_at')
                ->whereNull('q.deleted_at')
                ->where('q.created_by', $userId)
                ->whereBetween('qa.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(qa.created_at)'))
                ->orderBy('date')
                ->get();

            /*
             * ---------- Average score over time on this examiner's quizzes ----------
             */
            $scoresOverTime = DB::table('quizz_results as qr')
                ->join('quizz_attempts as qa', 'qa.id', '=', 'qr.attempt_id')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->select(
                    DB::raw('DATE(qr.created_at) as date'),
                    DB::raw('AVG(qr.percentage) as avg_percentage')
                )
                ->whereNull('qa.deleted_at')
                ->whereNull('q.deleted_at')
                ->where('q.created_by', $userId)
                ->whereBetween('qr.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(qr.created_at)'))
                ->orderBy('date')
                ->get();

            /*
             * ---------- Recent activity by this examiner ----------
             */
            $recentActivities = DB::table('user_data_activity_log')
                ->select('activity', 'module', 'performed_by', 'performed_by_role', 'log_note', 'created_at')
                ->where('performed_by', $userId)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            /*
             * ---------- Top quizzes by attempts (for this examiner) ----------
             */
            $topQuizzes = DB::table('quizz_attempts as qa')
                ->join('quizz as q', 'q.id', '=', 'qa.quiz_id')
                ->select(
                    'q.id',
                    'q.uuid',
                    'q.quiz_name',
                    DB::raw('COUNT(qa.id) as total_attempts'),
                    DB::raw('SUM(CASE WHEN qa.status IN ("submitted","auto_submitted") THEN 1 ELSE 0 END) as completed_attempts')
                )
                ->whereNull('qa.deleted_at')
                ->whereNull('q.deleted_at')
                ->where('q.created_by', $userId)
                ->whereBetween('qa.created_at', [$startDate, $endDate])
                ->groupBy('q.id', 'q.uuid', 'q.quiz_name')
                ->orderByDesc('total_attempts')
                ->limit(5)
                ->get();

            /*
             * ---------- Notifications summary (same as admin for now) ----------
             */
            $notificationsSummary = [
                'total_active' => DB::table('notifications')
                    ->where('status', 'active')
                    ->count(),
                'latest' => DB::table('notifications')
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(['id','title','message','priority','status','created_at']),
            ];

            return response()->json([
                'status'  => 'success',
                'message' => 'Examiner dashboard data fetched successfully',
                'data'    => [
                    'summary_counts'     => $summaryCounts,
                    'quick_stats'        => $quickStats,
                    'quizzes_over_time'  => $quizzesOverTime,
                    'attempts_over_time' => $attemptsOverTime,
                    'scores_over_time'   => $scoresOverTime,
                    'recent_activities'  => $recentActivities,
                    'top_quizzes'        => $topQuizzes,
                    'notifications'      => $notificationsSummary,
                    'date_range' => [
                        'start'  => $startDate->toDateString(),
                        'end'    => $endDate->toDateString(),
                        'period' => $period,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[Dashboard Examiner] Failed to build dashboard', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch examiner dashboard data',
            ], 500);
        }
    }

}
