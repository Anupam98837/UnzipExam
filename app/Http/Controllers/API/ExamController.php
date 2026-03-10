<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExamController extends Controller
{
    /* ============================================
     | Auth helpers (student via personal tokens)
     |============================================ */
    private const USER_TYPE = 'App\\Models\\User';

    /* ============================================
     | Auth helpers (role via CheckRole middleware)
     |============================================ */
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
        $a = $this->actor($request);

        if (!$a['role'] || !in_array($a['role'], $allowed, true)) {
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        return null;
    }

    private function getUserFromToken(Request $request): ?object
    {
        $header = (string) $request->header('Authorization', '');
        $token  = null;

        if (stripos($header, 'Bearer ') === 0) {
            $token = trim(substr($header, 7));
        } else {
            $token = trim($header);
        }
        if ($token === '') return null;

        $hashed = hash('sha256', $token);

        $pat = DB::table('personal_access_tokens')
            ->where('token', $hashed)
            ->where('tokenable_type', self::USER_TYPE)
            ->first();

        if (!$pat) return null;

        // expiry (optional column)
        if (isset($pat->expires_at) && $pat->expires_at !== null) {
            try {
                if (now()->greaterThan(Carbon::parse($pat->expires_at))) {
                    DB::table('personal_access_tokens')->where('id', $pat->id)->delete();
                    return null;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        $user = DB::table('users')
            ->where('id', $pat->tokenable_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$user) return null;
        if (isset($user->status) && $user->status !== 'active') return null;

        return $user;
    }

    private function isStudent(object $user): bool
    {
        $role = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)($user->role ?? '')));
        return in_array($role, ['student','std','stu'], true);
    }

    private function quizByKey(string|int $key): ?object
    {
        $q = DB::table('quizz')->whereNull('deleted_at');

        if (is_numeric($key)) {
            $q->where('id', (int) $key);
        } else {
            $key = (string) $key;

            $q->where(function ($w) use ($key) {
                $w->where('uuid', $key);

                // ALSO accept old columns if they exist
                if (Schema::hasColumn('quizz', 'quiz_uuid')) {
                    $w->orWhere('quiz_uuid', $key);
                }
                if (Schema::hasColumn('quizz', 'quiz_key')) {
                    $w->orWhere('quiz_key', $key);
                }
            });
        }

        return $q->first();
    }

    private function normalizeText(?string $s): string
    {
        $s = (string)$s;
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return mb_strtolower($s, 'UTF-8');
    }

    private function questionType(int $questionId): string
    {
        return (string) (DB::table('quizz_questions')->where('id', $questionId)->value('question_type') ?? 'mcq');
    }

    /* ============================================
     | POST /api/exam/start/{quizKey}
     |============================================ */
    public function start(Request $request, string $quizKey)
    {
        $user = $this->getUserFromToken($request);

        $quiz = $this->quizByKey($quizKey);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }

        if (($quiz->status ?? 'active') !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Quiz is not active',
            ], 409);
        }

        // Attempt limit
        $globalAllowed = max(1, (int) ($quiz->total_attempts ?? 1));

        $globalUsed = (int) DB::table('quizz_results')
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->count();

        if ($globalUsed >= $globalAllowed) {
            return response()->json([
                'success' => false,
                'message' => "Attempt limit reached for this quiz ({$globalUsed}/{$globalAllowed})",
            ], 429);
        }

        // running attempt (idempotent)
        $running = DB::table('quizz_attempts as qa')
            ->where('qa.quiz_id', $quiz->id)
            ->where('qa.user_id', $user->id)
            ->where('qa.status', 'in_progress')
            ->orderByDesc('qa.id')
            ->select('qa.*')
            ->first();

        $now         = Carbon::now();
        $durationMin = (int) ($quiz->total_time ?? 0);

        if ($durationMin <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz has no total_time set',
            ], 422);
        }

        $deadline = $now->copy()->addMinutes($durationMin);

        if ($running) {
            if (!empty($running->server_deadline_at) && $now->gte(Carbon::parse($running->server_deadline_at))) {
                $this->autoFinalize($running);
            } else {
                return response()->json([
                    'success' => true,
                    'attempt' => [
                        'attempt_uuid'   => $running->uuid,
                        'quiz_id'        => (int) $quiz->id,
                        'quiz_uuid'      => (string) $quiz->uuid,
                        'quiz_name'      => (string) ($quiz->quiz_name ?? 'Quiz'),
                        'total_time_sec' => $durationMin * 60,
                        'server_end_at'  => (string) $running->server_deadline_at,
                        'time_left_sec'  => max(
                            0,
                            Carbon::parse($running->server_deadline_at)->diffInSeconds($now, false) * -1
                        ),
                    ],
                ], 200);
            }
        }

        // Build per-attempt layout (questions + options order)
        $qRows = DB::table('quizz_questions')
            ->where('quiz_id', $quiz->id)
            ->orderBy('question_order')
            ->get(['id', 'question_type']);

        if ($qRows->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz has no questions',
            ], 422);
        }

        $questionIds = $qRows->pluck('id')->map(fn ($v) => (int) $v)->all();

        if (($quiz->is_question_random ?? 'no') === 'yes') {
            shuffle($questionIds);
        }

        $qTypes = [];
        foreach ($qRows as $qRow) {
            $qTypes[(int) $qRow->id] = (string) ($qRow->question_type ?? '');
        }

        $aRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $questionIds)
            ->orderBy('belongs_question_id')
            ->orderBy('answer_order')
            ->get(['id', 'belongs_question_id', 'answer_order']);

        $answersByQ = $aRows->groupBy('belongs_question_id');

        $optionsOrder = [];
        foreach ($questionIds as $qid) {
            $answers = $answersByQ[$qid] ?? collect();
            if ($answers->isEmpty()) {
                $optionsOrder[$qid] = [];
                continue;
            }

            $answerIds = $answers->pluck('id')->map(fn ($v) => (int) $v)->all();
            $qType     = $qTypes[$qid] ?? '';

            if (($quiz->is_option_random ?? 'no') === 'yes' && $qType !== 'fill_in_the_blank') {
                shuffle($answerIds);
            }

            $optionsOrder[$qid] = $answerIds;
        }

        $attemptUuid = (string) Str::uuid();

        $attemptId = DB::table('quizz_attempts')->insertGetId([
            'uuid'                => $attemptUuid,
            'quiz_id'             => (int) $quiz->id,
            'quiz_uuid'           => (string) ($quiz->uuid ?? null),
            'user_id'             => (int) $user->id,
            'status'              => 'in_progress',
            'total_time_sec'      => $durationMin * 60,
            'started_at'          => $now,
            'server_deadline_at'  => $deadline,
            'current_question_id' => null,
            'current_q_started_at'=> null,
            'last_activity_at'    => $now,
            'questions_order'     => json_encode($questionIds, JSON_UNESCAPED_UNICODE),
            'options_order'       => json_encode($optionsOrder, JSON_UNESCAPED_UNICODE),
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        return response()->json([
            'success' => true,
            'attempt' => [
                'attempt_id'     => $attemptId,
                'attempt_uuid'   => $attemptUuid,
                'quiz_id'        => (int) $quiz->id,
                'quiz_uuid'      => (string) $quiz->uuid,
                'quiz_name'      => (string) ($quiz->quiz_name ?? 'Quiz'),
                'total_time_sec' => $durationMin * 60,
                'server_end_at'  => (string) $deadline,
                'time_left_sec'  => max(0, $deadline->diffInSeconds($now, false) * -1),
            ],
        ], 201);
    }

    /* ============================================
     | GET /api/exam/quizzes/{quizKey}/my-attempts
     |============================================ */
    public function myAttemptsForQuiz(Request $request, string $quizKey)
    {
        $user = $this->getUserFromToken($request);
        if (!$user || !$this->isStudent($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized (student token required)'
            ], 401);
        }

        $quiz = $this->quizByKey($quizKey);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found'
            ], 404);
        }

        $q = DB::table('quizz_attempts as qa')
            ->where('qa.quiz_id', $quiz->id)
            ->where('qa.user_id', $user->id)
            ->leftJoin('quizz_results as qr', 'qr.attempt_id', '=', 'qa.id');

        $rows = $q->orderByDesc('qa.created_at')
            ->orderByDesc('qa.id')
            ->select([
                'qa.id as attempt_id',
                'qa.uuid as attempt_uuid',
                'qa.status',
                'qa.started_at',
                'qa.finished_at',
                'qa.server_deadline_at',
                'qa.total_time_sec',
                'qa.created_at',
                'qa.updated_at',

                'qr.id as result_id',
                'qr.marks_obtained',
                'qr.total_marks',
                'qr.percentage',
                'qr.attempt_number',
                'qr.publish_to_student',
            ])
            ->get();

        $allowViewNow = $this->shouldPublishToStudent((int)$quiz->id);

        $attempts = $rows->map(function ($row) use ($allowViewNow) {
            $canView = false;
            if ($row->result_id) {
                $canView = ((int)$row->publish_to_student === 1) || $allowViewNow;
            }

            return [
                'attempt_id'         => (int) $row->attempt_id,
                'attempt_uuid'       => (string) $row->attempt_uuid,
                'status'             => (string) $row->status,
                'started_at'         => $row->started_at ? Carbon::parse($row->started_at)->toDateTimeString() : null,
                'finished_at'        => $row->finished_at ? Carbon::parse($row->finished_at)->toDateTimeString() : null,
                'created_at'         => $row->created_at ? Carbon::parse($row->created_at)->toDateTimeString() : null,
                'server_deadline_at' => $row->server_deadline_at ? Carbon::parse($row->server_deadline_at)->toDateTimeString() : null,
                'total_time_sec'     => (int) ($row->total_time_sec ?? 0),

                'result' => $row->result_id ? [
                    'result_id'          => (int) $row->result_id,
                    'marks_obtained'     => (int) $row->marks_obtained,
                    'total_marks'        => (int) $row->total_marks,
                    'percentage'         => $row->total_marks
                        ? (float) ($row->percentage ?? round($row->marks_obtained / max(1, $row->total_marks) * 100, 2))
                        : 0.0,
                    'attempt_number'     => (int) ($row->attempt_number ?? 0),
                    'publish_to_student' => (int) $row->publish_to_student,
                    'can_view_detail'    => $canView,
                ] : null,
            ];
        })->values();

        $totalMarks = (int) DB::table('quizz_questions')
            ->where('quiz_id', $quiz->id)
            ->sum('question_mark');

        return response()->json([
            'success'  => true,
            'quiz'     => [
                'id'                     => (int) $quiz->id,
                'uuid'                   => (string) $quiz->uuid,
                'name'                   => (string) ($quiz->quiz_name ?? 'Quiz'),
                'total_marks'            => $totalMarks,
                'total_attempts_allowed' => (int) ($quiz->total_attempts ?? 1),
            ],
            'batch'    => null,
            'attempts' => $attempts,
        ], 200);
    }

    /* ============================================
 | GET /api/exam/attempts/{attempt}/questions
 |============================================ */
public function questions(Request $request, string $attemptUuid)
{
    $user = $this->getUserFromToken($request);
    if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

    $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
    if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
        return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
    }

    if ($this->deadlinePassed($attempt)) {
        $attempt = $this->autoFinalize($attempt, true);
    }

    $rows = DB::table('quizz_questions as q')
        ->leftJoin('quizz_question_answers as a', 'a.belongs_question_id', '=', 'q.id')
        ->where('q.quiz_id', $attempt->quiz_id)
        ->orderBy('q.question_order')
        ->orderBy('a.answer_order')
        ->select([
            'q.id as question_id',
            'q.question_title',
            'q.question_description',
            'q.answer_explanation',
            'q.question_type',
            'q.question_mark',
            'q.group_title',
            'q.question_order',
            DB::raw("(
                SELECT COUNT(*) FROM quizz_question_answers
                WHERE belongs_question_id = q.id AND is_correct = 1
            ) as correct_count"),

            'a.id as answer_id',
            'a.answer_title',
            'a.answer_order',
        ])
        ->get();

    $questionsById = [];
    foreach ($rows as $r) {
        $qid = (int)$r->question_id;

        if (!isset($questionsById[$qid])) {
            $questionsById[$qid] = [
                'question_id'                 => $qid,
                'question_title'              => $r->question_title,
                'question_description'        => $r->question_description,
                'question_type'               => $r->question_type,
                'question_mark'               => (int)$r->question_mark,
                'question_order'              => (int)$r->question_order,
                'group_title'                 => $r->group_title,
                'has_multiple_correct_answer' => ((int)$r->correct_count > 1),
                'answers'                     => [],
            ];
        }

        if ($r->answer_id !== null) {
            $questionsById[$qid]['answers'][] = [
                'answer_id'    => (int)$r->answer_id,
                'answer_title' => $r->answer_title,
                'answer_order' => (int)($r->answer_order ?? 0),
            ];
        }
    }

    $saved = DB::table('quizz_attempt_answers')
        ->where('attempt_id', $attempt->id)
        ->pluck('selected_raw', 'question_id');

    $selections = [];
    foreach ($saved as $qid => $json) {
        try { $selections[$qid] = json_decode($json, true); }
        catch (\Throwable $e) { $selections[$qid] = null; }
    }

    $layoutQuestions = null;
    $layoutOptions   = null;

    if (!empty($attempt->questions_order)) {
        try { $layoutQuestions = json_decode($attempt->questions_order, true); }
        catch (\Throwable $e) { $layoutQuestions = null; }
    }

    if (!empty($attempt->options_order)) {
        try { $layoutOptions = json_decode($attempt->options_order, true); }
        catch (\Throwable $e) { $layoutOptions = null; }
    }

    $orderedQuestions = [];
    $hasLayout = is_array($layoutQuestions) && !empty($layoutQuestions);

    if ($hasLayout) {
        $usedQids = [];
        foreach ($layoutQuestions as $qid) {
            $qid = (int)$qid;
            if (!isset($questionsById[$qid])) continue;

            $qArr = $questionsById[$qid];

            if (is_array($layoutOptions)) {
                $keyInt = $qid;
                $keyStr = (string)$qid;
                $ansOrder = $layoutOptions[$keyInt] ?? ($layoutOptions[$keyStr] ?? null);

                if (is_array($ansOrder) && !empty($qArr['answers'])) {
                    $answersById = [];
                    foreach ($qArr['answers'] as $ans) {
                        $answersById[(int)$ans['answer_id']] = $ans;
                    }

                    $newAnswers = [];
                    foreach ($ansOrder as $aid) {
                        $aid = (int)$aid;
                        if (isset($answersById[$aid])) {
                            $newAnswers[] = $answersById[$aid];
                            unset($answersById[$aid]);
                        }
                    }

                    foreach ($answersById as $aRow) $newAnswers[] = $aRow;
                    $qArr['answers'] = $newAnswers;
                }
            }

            $orderedQuestions[] = $qArr;
            $usedQids[$qid] = true;
        }

        foreach ($questionsById as $qid => $qArr) {
            if (!isset($usedQids[$qid])) $orderedQuestions[] = $qArr;
        }
    } else {
        $orderedQuestions = array_values($questionsById);
    }

    /* ==========================================================
     | ✅ NEW: Group-aware ordering (NO response schema change)
     | Ensures same group_title questions stay contiguous.
     | - Group order = first appearance in $orderedQuestions
     | - Within a group = preserves existing relative order
     | - Empty/null group_title stays per-question (no forced bunching)
     |========================================================== */
    $groupBuckets = [];
    $groupOrder   = [];

    foreach ($orderedQuestions as $qArr) {
        $gt = trim((string)($qArr['group_title'] ?? ''));

        // If no group, keep each question as its own "group" so we don't move them around
        $key = ($gt === '')
            ? ('__nogroup__:' . (int)$qArr['question_id'])
            : ('g:' . mb_strtolower($gt, 'UTF-8'));

        if (!isset($groupBuckets[$key])) {
            $groupBuckets[$key] = [];
            $groupOrder[] = $key;
        }
        $groupBuckets[$key][] = $qArr;
    }

    $groupedOut = [];
    foreach ($groupOrder as $k) {
        foreach ($groupBuckets[$k] as $qArr) {
            $groupedOut[] = $qArr;
        }
    }
    $orderedQuestions = $groupedOut;

    return response()->json([
        'success'=>true,
        'attempt'=>[
            'status'        => $attempt->status,
            'time_left_sec' => $this->timeLeftSec($attempt),
            'server_end_at' => (string)$attempt->server_deadline_at,
        ],
        'questions'  => $orderedQuestions,
        'selections' => $selections
    ], 200);
}


    /* ==========================================================
     | NEW: POST /api/exam/attempts/{attempt}/bulk-answer
     | Does NOT affect result APIs. Only saves answers faster.
     |========================================================== */
    public function bulkAnswer(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $v = Validator::make($request->all(), [
            'answers'                  => ['required','array','min:1'],
            'answers.*.question_id'    => ['required','integer','min:1'],
            'answers.*.selected'       => ['nullable'],
            'answers.*.time_spent_sec' => ['nullable','integer','min:0'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $now = Carbon::now();
        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
            return response()->json([
                'success'=>false,
                'message'=>'Time over — attempt auto-submitted',
                'attempt'=>['status'=>$attempt->status,'time_left_sec'=>0]
            ], 409);
        }

        $payload = $request->input('answers', []);
        $qIds = array_values(array_unique(array_map(fn($x)=> (int)($x['question_id'] ?? 0), $payload)));
        $qIds = array_values(array_filter($qIds, fn($x)=> $x > 0));

        $qMap = DB::table('quizz_questions')
            ->where('quiz_id', $attempt->quiz_id)
            ->whereIn('id', $qIds)
            ->get(['id','question_type'])
            ->keyBy('id');

        if ($qMap->count() !== count($qIds)) {
            return response()->json([
                'success'=>false,
                'message'=>'One or more questions are invalid for this quiz',
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($payload as $row) {
                $qid = (int)($row['question_id'] ?? 0);
                if ($qid <= 0) continue;

                $selected  = $row['selected'] ?? null;
                $timeSpent = (int)($row['time_spent_sec'] ?? 0);
                if ($timeSpent < 0) $timeSpent = 0;

                $qType = (string)($qMap[$qid]->question_type ?? 'mcq');
                $selectedJson = json_encode($selected, JSON_UNESCAPED_UNICODE);

                $existing = DB::table('quizz_attempt_answers')
                    ->where('attempt_id', $attempt->id)
                    ->where('question_id', $qid)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    DB::table('quizz_attempt_answers')->where('id', $existing->id)->update([
                        'selected_raw'   => $selectedJson,
                        'question_type'  => $existing->question_type ?: $qType,
                        'time_spent_sec' => (int)($existing->time_spent_sec ?? 0) + $timeSpent,
                        'answered_at'    => $existing->answered_at ?: $now,
                        'updated_at'     => $now,
                    ]);
                } else {
                    DB::table('quizz_attempt_answers')->insert([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $qid,
                        'question_type'  => $qType,
                        'selected_raw'   => $selectedJson,
                        'time_spent_sec' => $timeSpent,
                        'answered_at'    => $now,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }
            }

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'last_activity_at' => $now,
                'updated_at'       => $now,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'attempt' => [
                    'time_left_sec' => $this->timeLeftSec($attempt),
                    'server_end_at' => (string)$attempt->server_deadline_at,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Exam bulkAnswer] failed', ['e'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to save answers'], 500);
        }
    }

    /* ============================================
     | Legacy: focus
     |============================================ */
    public function focus(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $v = Validator::make($request->all(), [
            'question_id' => ['required','integer','min:1'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $now = Carbon::now();
        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
            return response()->json([
                'success'=>false,
                'message'=>'Time over — attempt auto-submitted',
                'attempt'=>['status'=>$attempt->status,'time_left_sec'=>0]
            ], 409);
        }

        $questionId = (int)$request->input('question_id');

        $qExists = DB::table('quizz_questions')
            ->where('id', $questionId)
            ->where('quiz_id', $attempt->quiz_id)
            ->exists();
        if (!$qExists) return response()->json(['success'=>false,'message'=>'Invalid question'], 422);

        DB::table('quizz_attempts')->where('id', $attempt->id)->update([
            'current_question_id'  => $questionId,
            'current_q_started_at' => $now,
            'last_activity_at'     => $now,
            'updated_at'           => $now,
        ]);

        return response()->json([
            'success'=>true,
            'attempt'=>[
                'time_left_sec' => $this->timeLeftSec($attempt),
                'server_end_at' => (string)$attempt->server_deadline_at,
            ]
        ], 200);
    }

    /* ============================================
     | Legacy: saveAnswer
     |============================================ */
    public function saveAnswer(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $v = Validator::make($request->all(), [
            'question_id' => ['required','integer','min:1'],
            'selected'    => ['nullable'],
            'time_spent'  => ['nullable','integer','min:0'],
        ]);
        if ($v->fails()) return response()->json(['success'=>false,'errors'=>$v->errors()], 422);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['success'=>false,'message'=>'Attempt is not running'], 409);
        }

        $now = Carbon::now();
        if ($this->deadlinePassed($attempt)) {
            $attempt = $this->autoFinalize($attempt, true);
            return response()->json([
                'success'=>false,
                'message'=>'Time over — attempt auto-submitted',
                'attempt'=>['status'=>$attempt->status,'time_left_sec'=>0]
            ], 409);
        }

        $questionId = (int)$request->input('question_id');

        $qRow = DB::table('quizz_questions')
            ->where('id', $questionId)
            ->where('quiz_id', $attempt->quiz_id)
            ->first(['id','question_type']);
        if (!$qRow) return response()->json(['success'=>false,'message'=>'Invalid question'], 422);

        $qType = (string)($qRow->question_type ?? 'mcq');
        $slice = (int) $request->input('time_spent', 0);
        if ($slice < 0) $slice = 0;

        DB::beginTransaction();
        try {
            $selectedJson = json_encode($request->input('selected', null), JSON_UNESCAPED_UNICODE);

            $row = DB::table('quizz_attempt_answers')
                ->where('attempt_id', $attempt->id)
                ->where('question_id', $questionId)
                ->lockForUpdate()
                ->first();

            if ($row) {
                DB::table('quizz_attempt_answers')->where('id',$row->id)->update([
                    'selected_raw'   => $selectedJson,
                    'question_type'  => $row->question_type ?: $qType,
                    'time_spent_sec' => (int)($row->time_spent_sec ?? 0) + $slice,
                    'answered_at'    => $row->answered_at ?: $now,
                    'updated_at'     => $now,
                ]);
            } else {
                DB::table('quizz_attempt_answers')->insert([
                    'attempt_id'     => $attempt->id,
                    'question_id'    => $questionId,
                    'question_type'  => $qType,
                    'selected_raw'   => $selectedJson,
                    'time_spent_sec' => $slice,
                    'answered_at'    => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'current_question_id'  => $questionId,
                'current_q_started_at' => $now,
                'last_activity_at'     => $now,
                'updated_at'           => $now,
            ]);

            DB::commit();

            return response()->json([
                'success'=>true,
                'attempt'=>[
                    'time_left_sec' => $this->timeLeftSec($attempt),
                    'server_end_at' => (string)$attempt->server_deadline_at,
                ]
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Exam saveAnswer] failed', ['e'=>$e->getMessage()]);
            return response()->json(['success'=>false,'message'=>'Failed to save answer'], 500);
        }
    }

    /* ============================================
     | Submit (keeps old behavior + OPTIONAL answers payload)
     | POST /api/exam/attempts/{attempt}/submit
     | body optional: { answers: [...] } same as bulkAnswer
     |============================================ */
    private function writeAttemptAnswerDerived(int $attemptId, array $breakdown): void
{
    if (empty($breakdown)) return;

    $now = now();

    // 1 query to know which rows already exist
    $existingQids = DB::table('quizz_attempt_answers')
        ->where('attempt_id', $attemptId)
        ->pluck('question_id')
        ->map(fn($v) => (int)$v)
        ->flip()
        ->all();

    $toInsert   = [];
    $qTypeCache = [];  // avoid re-querying same question_type

    foreach ($breakdown as $snap) {
        $qid = (int) $snap['question_id'];

        // Derive selected_answer_ids + selected_text from new format
        if (array_key_exists('answer_ids', $snap)) {
            // multi-select
            $ansIds  = $snap['answer_ids'];             // int[]
            $ansText = null;
        } elseif (array_key_exists('answer_text', $snap)) {
            // fill in the blank
            $ansIds  = null;
            $ansText = (string)($snap['answer_text'] ?? '');
        } else {
            // single MCQ — answer_id (int|null)
            $aid     = $snap['answer_id'] ?? null;
            $ansIds  = is_null($aid) ? null : [$aid];
            $ansText = null;
        }

        $upd = [
            'is_correct'          => (int) $snap['is_correct'],
            'awarded_mark'        => (int) $snap['awarded_mark'],
            'selected_answer_ids' => $ansIds !== null
                ? json_encode($ansIds, JSON_UNESCAPED_UNICODE)
                : null,
            'selected_text'       => $ansText,
            'updated_at'          => $now,
        ];

        if (isset($existingQids[$qid])) {
            DB::table('quizz_attempt_answers')
                ->where('attempt_id', $attemptId)
                ->where('question_id', $qid)
                ->update($upd + ['answered_at' => DB::raw('COALESCE(answered_at, NOW())')]);
        } else {
            if (!isset($qTypeCache[$qid])) {
                $qTypeCache[$qid] = DB::table('quizz_questions')
                    ->where('id', $qid)->value('question_type') ?? 'mcq';
            }
            $toInsert[] = array_merge($upd, [
                'attempt_id'     => $attemptId,
                'question_id'    => $qid,
                'question_type'  => $qTypeCache[$qid],
                'selected_raw'   => null,
                'time_spent_sec' => 0,
                'answered_at'    => $now,
                'created_at'     => $now,
            ]);
        }
    }

    if (!empty($toInsert)) {
        DB::table('quizz_attempt_answers')->insert($toInsert);
    }
}
    /**
 * Normalize a single snapshot row to the new format.
 * Old format used: selected, selected_answer_ids, selected_text
 * New format uses: answer_id | answer_ids | answer_text, time_sec
 */
private function normalizeSnap(array $snap): array
{
    // Already new format — has at least one of the new keys
    if (
        array_key_exists('answer_id', $snap) ||
        array_key_exists('answer_ids', $snap) ||
        array_key_exists('answer_text', $snap)
    ) {
        return $snap;
    }

    // ---- Legacy format → convert ----
    $ids  = $snap['selected_answer_ids'] ?? null;   // null | int[] 
    $text = $snap['selected_text'] ?? null;          // string | null

    // FIB: had selected_text, no ids
    if ($text !== null && (is_null($ids) || $ids === [])) {
        $snap['answer_text'] = $text;
    }
    // Multi-select: array with more than 1 id
    elseif (is_array($ids) && count($ids) > 1) {
        $snap['answer_ids'] = array_values(array_map('intval', $ids));
    }
    // Single MCQ: array with 1 id, or a bare int
    else {
        $snap['answer_id'] = is_array($ids) ? ($ids[0] ?? null) : (is_null($ids) ? null : (int)$ids);
    }

    // Legacy had no per-question time in the snapshot
    if (!array_key_exists('time_sec', $snap)) {
        $snap['time_sec'] = 0;
    }

    // Keep selected for FIB fibExplain compat — don't remove it
    return $snap;
}

    public function submit(Request $request, string $attemptUuid)
{
    $user = $this->getUserFromToken($request);
    if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

    $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
    if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
        return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
    }

    // Already submitted — return existing result idempotently
    if (in_array($attempt->status, ['submitted','auto_submitted'], true)) {
        $summary = $this->resultSummaryForAttempt($attempt);
        return response()->json(['success'=>true] + $summary, 200);
    }

    // Deadline passed — auto finalize and return
    if ($this->deadlinePassed($attempt)) {
        $attempt = $this->autoFinalize($attempt, true);
        $summary = $this->resultSummaryForAttempt($attempt);
        return response()->json(['success'=>true] + $summary, 200);
    }

    $now = Carbon::now();

    // ========================================================
    // STEP 1 — Save any answers posted inline with submit
    // Done OUTSIDE the transaction — these are safe idempotent
    // writes and don't need to be atomic with result creation
    // ========================================================
    if ($request->has('answers') && is_array($request->input('answers'))) {
        $payload = $request->input('answers', []);

        $qIds = array_values(array_unique(
            array_filter(
                array_map(fn($x) => (int)($x['question_id'] ?? 0), $payload),
                fn($x) => $x > 0
            )
        ));

        if (!empty($qIds)) {
            $qMap = DB::table('quizz_questions')
                ->where('quiz_id', $attempt->quiz_id)
                ->whereIn('id', $qIds)
                ->get(['id','question_type'])
                ->keyBy('id');

            if ($qMap->count() !== count($qIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more questions are invalid for this quiz',
                ], 422);
            }

            foreach ($payload as $row) {
                $qid = (int)($row['question_id'] ?? 0);
                if ($qid <= 0) continue;

                $selected     = $row['selected'] ?? null;
                $timeSpent    = max(0, (int)($row['time_spent_sec'] ?? 0));
                $qType        = (string)($qMap[$qid]->question_type ?? 'mcq');
                $selectedJson = json_encode($selected, JSON_UNESCAPED_UNICODE);

                $existing = DB::table('quizz_attempt_answers')
                    ->where('attempt_id', $attempt->id)
                    ->where('question_id', $qid)
                    ->first();

                if ($existing) {
                    DB::table('quizz_attempt_answers')
                        ->where('id', $existing->id)
                        ->update([
                            'selected_raw'   => $selectedJson,
                            'question_type'  => $existing->question_type ?: $qType,
                            'time_spent_sec' => (int)($existing->time_spent_sec ?? 0) + $timeSpent,
                            'answered_at'    => $existing->answered_at ?: $now,
                            'updated_at'     => $now,
                        ]);
                } else {
                    DB::table('quizz_attempt_answers')->insert([
                        'attempt_id'     => $attempt->id,
                        'question_id'    => $qid,
                        'question_type'  => $qType,
                        'selected_raw'   => $selectedJson,
                        'time_spent_sec' => $timeSpent,
                        'answered_at'    => $now,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }
            }

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'last_activity_at' => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    // ========================================================
    // STEP 2 — All reads/computation OUTSIDE the transaction
    // scoreAttempt fires 3 flat queries — no reason to hold
    // a DB lock while PHP is doing scoring logic
    // ========================================================
    $scored = $this->scoreAttempt($attempt->id);

    // Fetch quiz data once — avoids 2 separate ->value() calls
    // inside the transaction
    $quiz = DB::table('quizz')
        ->where('id', $attempt->quiz_id)
        ->first(['result_set_up_type', 'result_release_date']);

    // Compute attempt number before opening transaction
    $nextAttemptNumber = $this->attemptNumberForUser(
        (int)$attempt->quiz_id,
        (int)$attempt->user_id
    ) + 1;

    // Compute publish flag before opening transaction
    $publish = $this->shouldPublishToStudent((int)$attempt->quiz_id);

    $cnt = $scored['counters'];
    $pct = $scored['total_marks']
        ? round($scored['marks_obtained'] / $scored['total_marks'] * 100, 2)
        : 0;

    // ========================================================
    // STEP 3 — Transaction is now TINY: just 3 writes
    // writeAttemptAnswerDerived  → 1 SELECT + N updates + 1 INSERT
    // quizz_results INSERT        → 1 query
    // quizz_attempts UPDATE       → 1 query
    // ========================================================
    DB::beginTransaction();
    try {
        // Write scored derived columns back to attempt_answers
        // Uses new snapshot format (answer_id / answer_ids / answer_text + time_sec)
        $this->writeAttemptAnswerDerived($attempt->id, $scored['answers']);

        $resultId = DB::table('quizz_results')->insertGetId([
            'uuid'               => (string) Str::uuid(),
            'attempt_id'         => (int) $attempt->id,
            'quiz_id'            => (int) $attempt->quiz_id,
            'user_id'            => (int) $attempt->user_id,
            'marks_obtained'     => (int) $scored['marks_obtained'],
            'total_marks'        => (int) $scored['total_marks'],
            'marks_total'        => (int) $scored['total_marks'],
            'total_questions'    => (int) $cnt['total_questions'],
            'total_correct'      => (int) $cnt['total_correct'],
            'total_incorrect'    => (int) $cnt['total_incorrect'],
            'total_skipped'      => (int) $cnt['total_skipped'],
            'percentage'         => $pct,
            'attempt_number'     => $nextAttemptNumber,
            // students_answer now stores new compact format:
            // [{question_id, answer_id|answer_ids|answer_text, is_correct, awarded_mark, time_sec}]
            'students_answer'    => json_encode($scored['answers'], JSON_UNESCAPED_UNICODE),
            'publish_to_student' => $publish ? 1 : 0,
            'result_set_up_type' => $quiz->result_set_up_type ?? 'Immediately',
            'result_release_date'=> $quiz->result_release_date ?? null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        DB::table('quizz_attempts')->where('id', $attempt->id)->update([
            'status'      => 'submitted',
            'finished_at' => $now,
            'updated_at'  => $now,
            'result_id'   => $resultId,
        ]);

        DB::commit();

        $attempt = DB::table('quizz_attempts')->where('id', $attempt->id)->first();
        $summary = $this->resultSummaryForAttempt($attempt);

        return response()->json(['success'=>true] + $summary, 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('[Exam submit] failed', [
            'e'           => $e->getMessage(),
            'attempt_uuid'=> $attemptUuid,
            'trace'       => $e->getTraceAsString(),
        ]);
        return response()->json(['success'=>false,'message'=>'Failed to submit'], 500);
    }
}
    /* ============================================
     | Status
     |============================================ */
    public function status(Request $request, string $attemptUuid)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['success'=>false,'message'=>'Unauthorized'], 401);

        $attempt = DB::table('quizz_attempts')->where('uuid', $attemptUuid)->first();
        if (!$attempt || (int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['success'=>false,'message'=>'Attempt not found'], 404);
        }

        if ($this->deadlinePassed($attempt) && $attempt->status === 'in_progress') {
            $attempt = $this->autoFinalize($attempt, true);
        }

        return response()->json([
            'success'=>true,
            'attempt'=>[
                'status'        => $attempt->status,
                'time_left_sec' => $this->timeLeftSec($attempt),
                'server_end_at' => (string)$attempt->server_deadline_at,
            ]
        ], 200);
    }

    /* ============================================
     | Answer Sheet (RESULT API - unchanged)
     |============================================ */
    public function answerSheet(int $resultId)
    {
        $res = DB::table('quizz_results')->where('id', $resultId)->first();
        if (!$res) abort(404, 'Result not found');

        $user  = DB::table('users')->where('id', $res->user_id)->first();
        $quiz  = DB::table('quizz')->where('id', $res->quiz_id)->first();
        $ans   = json_decode($res->students_answer ?? '[]', true) ?: [];

        $questions = DB::table('quizz_questions')
            ->where('quiz_id', $res->quiz_id)
            ->orderBy('question_order')
            ->get();

        $answerRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $questions->pluck('id'))
            ->orderBy('belongs_question_id')
            ->orderBy('answer_order')
            ->get()
            ->groupBy('belongs_question_id');

        $totalMarks = (int) $questions->sum('question_mark');
        $pct = $totalMarks ? round(((int)$res->marks_obtained / $totalMarks) * 100) : 0;
        $passFail = $pct >= 60 ? 'PASS' : 'FAIL';

        return Response::streamDownload(function () use ($user,$quiz,$ans,$questions,$answerRows,$res,$totalMarks,$pct,$passFail) {
            $safe = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
            echo "<!doctype html><html><head><meta charset='utf-8'><title>Answer Sheet</title>
<style>
@page { size:A4; margin:0 }
body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:20px;color:#222}
h1,h2{margin:6px 0}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:12px}
.badge{display:inline-block;padding:4px 8px;border-radius:14px;background:#eef2ff}
.meta{font-size:13px;color:#666}
.sep{height:1px;background:#eee;margin:10px 0}
.correct{color:#166534}
.wrong{color:#991b1b}
</style></head><body>";

            echo "<div class='card'><h1>{$safe($quiz->quiz_name ?? 'Exam')}</h1>
<div class='meta'>Student: {$safe($user->name ?? ('#'.$user->id))}</div>
<div class='meta'>Score: <b>{$res->marks_obtained}/{$totalMarks}</b> • {$pct}% • <span class='badge'>{$passFail}</span></div>
</div>";

            foreach ($questions as $q) {
                $qAns = $answerRows[$q->id] ?? collect();
                $correctIds = $qAns->where('is_correct', 1)->pluck('id')->values()->all();

                $stuSel = null;
                foreach ($ans as $row) {
    if ((int)($row['question_id'] ?? 0) === (int)$q->id) {
        $row = $this->normalizeSnap($row);
        // For FIB fibExplain needs the raw text; for MCQ it needs an id or array of ids
        if (array_key_exists('answer_text', $row)) {
            $stuSel = $row['answer_text'];
        } elseif (array_key_exists('answer_ids', $row)) {
            $stuSel = $row['answer_ids'];
        } else {
            $stuSel = $row['answer_id'] ?? null;
        }
        break;
    }
}

                // derive correctness (for print)
                if ($q->question_type === 'fill_in_the_blank') {
                    $ex = $this->fibExplain($q, $qAns, $stuSel);
                    $correct     = $ex['correct'];
                    $stuDisplay  = $ex['student_display'];
                    $corrDisplay = $ex['correct_display'];
                } else {
                    $correct = false;
                    if (is_array($stuSel)) {
                        $l = $stuSel; sort($l);
                        $r = $correctIds; sort($r);
                        $correct = ($l === $r);
                        $labels = [];
                        foreach ($qAns as $a) if (in_array($a->id, $l)) $labels[] = $a->answer_title;
                        $stuDisplay = implode(', ', $labels);
                    } else {
                        $correct = ((int)$stuSel === (int)($correctIds[0] ?? -1));
                        $label = '';
                        foreach ($qAns as $a) if ((int)$a->id === (int)$stuSel) { $label = $a->answer_title; break; }
                        $stuDisplay = $label;
                    }
                    $corrDisplay = '';
                    foreach ($qAns as $a) if (in_array($a->id, $correctIds)) { $corrDisplay = $a->answer_title; break; }
                }

                echo "<div class='card'><div><b>Q{$q->question_order}.</b> {$safe($q->question_title)}</div>
<div class='meta'>Marks: {$q->question_mark} • Type: {$q->question_type}</div><div class='sep'></div>";

                if ($correct) {
                    echo "<div class='correct'><b>Correct</b></div>";
                } else {
                    echo "<div class='wrong'><b>Incorrect</b></div>";
                    echo "<div>Correct: {$safe($corrDisplay)}</div>";
                }
                if ($stuDisplay !== '') echo "<div>Your answer: {$safe($stuDisplay)}</div>";
                echo "</div>";
            }
            echo "</body></html>";
        }, "answer_sheet_{$resultId}.html", ['Content-Type' => 'text/html']);
    }

    /* ==================== internals ==================== */

    private function deadlinePassed(object $attempt): bool
    {
        try { return Carbon::now()->gte(Carbon::parse($attempt->server_deadline_at)); }
        catch (\Throwable $e) { return false; }
    }

    private function timeLeftSec(object $attempt): int
    {
        try {
            $left = Carbon::now()->diffInSeconds(Carbon::parse($attempt->server_deadline_at), false);
            return max(0, $left);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function autoFinalize(object $attempt, bool $refresh = false): object
    {
        $now = Carbon::now();

        DB::beginTransaction();
        try {
            $scored  = $this->scoreAttempt($attempt->id);
            $this->writeAttemptAnswerDerived($attempt->id, $scored['answers']);
            $publish = $this->shouldPublishToStudent((int)$attempt->quiz_id);

            $cnt = $scored['counters'];
            $pct = $scored['total_marks'] ? round($scored['marks_obtained'] / $scored['total_marks'] * 100, 2) : 0;

            $resultId = DB::table('quizz_results')->insertGetId([
                'uuid'               => (string) Str::uuid(),
                'attempt_id'         => (int) $attempt->id,
                'quiz_id'            => (int) $attempt->quiz_id,
                'user_id'            => (int) $attempt->user_id,
                'marks_obtained'     => (int) $scored['marks_obtained'],
                'total_marks'        => (int) $scored['total_marks'],
                'marks_total'        => (int) $scored['total_marks'],
                'total_questions'    => (int) $cnt['total_questions'],
                'total_correct'      => (int) $cnt['total_correct'],
                'total_incorrect'    => (int) $cnt['total_incorrect'],
                'total_skipped'      => (int) $cnt['total_skipped'],
                'percentage'         => $pct,
                'attempt_number'     => (int) $this->attemptNumberForUser((int)$attempt->quiz_id, (int)$attempt->user_id) + 1,
                'students_answer'    => json_encode($scored['answers'], JSON_UNESCAPED_UNICODE),
                'publish_to_student' => $publish ? 1 : 0,
                'result_set_up_type' => DB::table('quizz')->where('id',$attempt->quiz_id)->value('result_set_up_type') ?? 'Immediately',
                'result_release_date'=> DB::table('quizz')->where('id',$attempt->quiz_id)->value('result_release_date'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            DB::table('quizz_attempts')->where('id', $attempt->id)->update([
                'status'      => 'auto_submitted',
                'finished_at' => $now,
                'updated_at'  => $now,
                'result_id'   => $resultId,
            ]);

            DB::commit();

            return $refresh
                ? DB::table('quizz_attempts')->where('id',$attempt->id)->first()
                : $attempt;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Exam autoFinalize] failed', ['e'=>$e->getMessage()]);
            return $attempt;
        }
    }

    private function attemptNumberForUser(int $quizId, int $userId): int
    {
        return (int) DB::table('quizz_results')
            ->where('quiz_id', $quizId)
            ->where('user_id', $userId)
            ->count();
    }

    private function shouldPublishToStudent(int $quizId): bool
    {
        $q = DB::table('quizz')->where('id', $quizId)->first(['result_set_up_type','result_release_date']);
        $type = (string)($q->result_set_up_type ?? 'Immediately');
        if ($type === 'Immediately' || $type === 'Now') return true;
        if ($type === 'Schedule' && !empty($q->result_release_date)) {
            try {
                return Carbon::now()->gte(Carbon::parse($q->result_release_date));
            } catch (\Throwable $e) { return false; }
        }
        return false;
    }

    /* ===== FIB helpers ===== */

    private function fibCountGaps(object $q, $answers): int
    {
        $title = (string)($q->question_title ?? '');
        $desc  = (string)($q->question_description ?? '');
        $n = 0;
        try {
            $n += preg_match_all('/\{dash\}/i', $title);
            $n += preg_match_all('/\{dash\}/i', $desc);
        } catch (\Throwable $e) {}
        if ($n > 0) return (int)$n;

        if ($answers && method_exists($answers, 'pluck')) {
            $orders = $answers->pluck('answer_order')
                ->filter(fn($v) => is_numeric($v) && (int)$v > 0)
                ->unique()->count();
            if ($orders > 0) return (int)$orders;
            return max(1, (int)$answers->count());
        }
        return 1;
    }

    private function fibExplain(object $q, $answers, $selRaw): array
    {
        $stuArr = [];
        if (is_array($selRaw))       $stuArr = array_values(array_map('strval', $selRaw));
        else if (trim((string)$selRaw) !== '') $stuArr = [ (string)$selRaw ];

        $gaps = $this->fibCountGaps($q, $answers);

        $distinctOrders = 0;
        if ($answers && method_exists($answers, 'pluck')) {
            $distinctOrders = (int) $answers->pluck('answer_order')
                ->filter(fn($v) => is_numeric($v) && (int)$v > 0)
                ->unique()->count();
        }
        $perGap = ($gaps > 1) || ($distinctOrders > 1);
        $norm = fn($s) => $this->normalizeText((string)$s);

        if (!$perGap) {
            $stuCombined = $norm(is_array($selRaw) ? implode(' ', $selRaw) : (string)$selRaw);
            $correct = false; $correctText = '';
            if ($answers) {
                foreach ($answers as $a) {
                    $src = (string) ($a->answer_two_gap_match ?? $a->answer_title ?? '');
                    if ($correctText === '' && $src !== '') $correctText = $src;
                    if ($stuCombined !== '' && $norm($src) !== '' && $stuCombined === $norm($src)) {
                        $correct = true; break;
                    }
                }
            }
            return [
                'correct'         => $correct,
                'student_display' => is_array($selRaw) ? implode(' | ', $stuArr) : (string)$selRaw,
                'correct_display' => $correctText,
            ];
        }

        $allOk = true; $studentParts = []; $correctParts = [];
        for ($i = 1; $i <= $gaps; $i++) {
            $allowed = ($answers && method_exists($answers, 'where')) ? $answers->where('answer_order', $i) : null;
            if (!$allowed || (method_exists($allowed, 'isEmpty') && $allowed->isEmpty())) {
                $row = ($answers && method_exists($answers, 'values')) ? $answers->values()->get($i-1) : null;
                $allowed = $row ? collect([$row]) : collect();
            }

            $stuRaw = (string)($stuArr[$i-1] ?? '');
            $studentParts[] = $stuRaw;
            $first = (method_exists($allowed, 'first') ? $allowed->first() : null);
            $correctParts[] = $first ? (string)($first->answer_two_gap_match ?? $first->answer_title ?? '') : '';

            $stu = $norm($stuRaw);
            $hit = false;
            if ($stu !== '' && $allowed) {
                foreach ($allowed as $a) {
                    $needle = $norm($a->answer_two_gap_match ?? $a->answer_title ?? '');
                    if ($needle !== '' && $stu === $needle) { $hit = true; break; }
                }
            }
            if (!$hit) $allOk = false;
        }

        return [
            'correct'         => $allOk,
            'student_display' => implode(' | ', $studentParts),
            'correct_display' => implode(' | ', $correctParts),
        ];
    }
private function scoreAttempt(int $attemptId): array
{
    $attempt = DB::table('quizz_attempts')->where('id', $attemptId)->first();
    if (!$attempt) {
        return [
            'marks_obtained' => 0, 'total_marks' => 0, 'answers' => [],
            'counters' => ['total_questions'=>0,'total_correct'=>0,'total_incorrect'=>0,'total_skipped'=>0]
        ];
    }

    // 3 flat queries — no per-question queries
    $qRows = DB::table('quizz_questions')
        ->where('quiz_id', $attempt->quiz_id)
        ->orderBy('question_order')
        ->get();

    $aRows = DB::table('quizz_question_answers')
        ->whereIn('belongs_question_id', $qRows->pluck('id'))
        ->orderBy('belongs_question_id')
        ->orderBy('answer_order')
        ->get()
        ->groupBy('belongs_question_id');

    $saved = DB::table('quizz_attempt_answers')
        ->where('attempt_id', $attemptId)
        ->get()
        ->keyBy('question_id');

    $marksObtained  = 0;
    $totalMarks     = (int) $qRows->sum('question_mark');
    $totalCorrect   = 0;
    $totalIncorrect = 0;
    $totalSkipped   = 0;
    $snapshot       = [];

    foreach ($qRows as $q) {
        $qid     = (int) $q->id;
        $answers = $aRows[$qid] ?? collect();
        $savedRow = $saved[$qid] ?? null;
        $selRaw  = null;

        if ($savedRow) {
            try { $selRaw = json_decode($savedRow->selected_raw ?? 'null', true); }
            catch (\Throwable $e) { $selRaw = null; }
        }

        // Time spent is already stored in quizz_attempt_answers
        $timeSec = $savedRow ? (int)($savedRow->time_spent_sec ?? 0) : 0;

        $type        = (string) $q->question_type;
        $awarded     = 0;
        $isCorrect   = false;

        if ($type === 'fill_in_the_blank') {
            $fib       = $this->fibExplain($q, $answers, $selRaw);
            $isCorrect = $fib['correct'];
            $awarded   = $isCorrect ? (int) $q->question_mark : 0;

            // ── NEW snapshot format for FIB ──
            $snapEntry = [
                'question_id' => $qid,
                'answer_text' => $fib['student_display'],   // string
                'is_correct'  => $isCorrect ? 1 : 0,
                'awarded_mark'=> $awarded,
                'time_sec'    => $timeSec,
            ];

        } else {
            $correctIds = $answers->where('is_correct', 1)->pluck('id')->values()->all();
            $isMulti    = count($correctIds) > 1;

            if ($isMulti) {
                $selected = is_array($selRaw)
                    ? array_values(array_map('intval', $selRaw))
                    : [];
                $l = $selected; sort($l);
                $r = $correctIds; sort($r);
                $isCorrect = ($l === $r);

                // ── NEW snapshot format for multi-select ──
                $snapEntry = [
                    'question_id' => $qid,
                    'answer_ids'  => $selected,             // int[]
                    'is_correct'  => $isCorrect ? 1 : 0,
                    'awarded_mark'=> $isCorrect ? (int)$q->question_mark : 0,
                    'time_sec'    => $timeSec,
                ];
            } else {
                $selectedId = is_null($selRaw) ? null : (int)$selRaw;
                $isCorrect  = ($selectedId === (int)($correctIds[0] ?? -1));

                // ── NEW snapshot format for single MCQ ──
                $snapEntry = [
                    'question_id' => $qid,
                    'answer_id'   => $selectedId,           // int | null
                    'is_correct'  => $isCorrect ? 1 : 0,
                    'awarded_mark'=> $isCorrect ? (int)$q->question_mark : 0,
                    'time_sec'    => $timeSec,
                ];
            }

            $awarded = $snapEntry['awarded_mark'];
        }

        // Skipped / correct / incorrect counters
        $isEmpty = ($selRaw === null) ||
            (is_array($selRaw) && count(array_filter($selRaw, fn($v) => trim((string)$v) !== '')) === 0);

        if ($isEmpty)       $totalSkipped++;
        elseif ($isCorrect) $totalCorrect++;
        else                $totalIncorrect++;

        $marksObtained += $awarded;
        $snapshot[]     = $snapEntry;
    }

    return [
        'marks_obtained' => $marksObtained,
        'total_marks'    => $totalMarks,
        'answers'        => $snapshot,
        'counters'       => [
            'total_questions' => (int) $qRows->count(),
            'total_correct'   => $totalCorrect,
            'total_incorrect' => $totalIncorrect,
            'total_skipped'   => $totalSkipped,
        ],
    ];
}
    private function resultSummaryForAttempt(object $attempt): array
    {
        $res = $attempt->result_id
            ? DB::table('quizz_results')->where('id', $attempt->result_id)->first()
            : null;

        return [
            'attempt' => [
                'status'      => $attempt->status,
                'finished_at' => (string)($attempt->finished_at ?? ''),
                'result_id'   => $attempt->result_id ?? null,
            ],
            'result' => $res ? [
                'result_id'         => $res->id,
                'marks_obtained'    => (int)$res->marks_obtained,
                'total_marks'       => (int)$res->total_marks,
                'percentage'        => ($res->total_marks ? round($res->marks_obtained / $res->total_marks * 100) : 0),
                'publish_to_student'=> (int)$res->publish_to_student,
            ] : null,
        ];
    }

    /* ==========================================================
     | RESULT APIs BELOW — kept same as your previous version
     | resultDetail(), resultDetailForInstructor(), export(),
     | assignedResultsForQuiz()
     |========================================================== */

     public function resultDetail(Request $request, string $resultKey)
{
    // ---------- 1. Load result + attempt + quiz (by ID or UUID) ----------
    $row = DB::table('quizz_results as r')
        ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
        ->join('quizz as q', 'q.id', '=', 'r.quiz_id')
        ->where(function ($w) use ($resultKey) {
            if (is_numeric($resultKey)) {
                $w->where('r.id', (int) $resultKey);
            } else {
                $w->where('r.uuid', (string) $resultKey);
            }
        })
        ->select([
            'r.id as result_id',
            'r.uuid as result_uuid',
            'r.quiz_id',
            'r.attempt_id',
            'r.user_id',
            'r.marks_obtained',
            'r.total_marks',
            'r.total_questions',
            'r.total_correct',
            'r.total_incorrect',
            'r.total_skipped',
            'r.percentage',
            'r.attempt_number',
            'r.students_answer',
            'r.publish_to_student',
            'r.result_set_up_type',
            'r.result_release_date',
            'r.created_at as result_created_at',

            'a.uuid as attempt_uuid',
            'a.status as attempt_status',
            'a.started_at',
            'a.finished_at',
            'a.total_time_sec',
            'a.server_deadline_at',

            'q.quiz_name',
            'q.quiz_description',
            'q.total_time',
            'q.total_questions as quiz_total_questions',
        ])
        ->first();

    if (!$row) {
        return response()->json([
            'success' => false,
            'message' => 'Result not found'
        ], 404);
    }

    // ---------- 2. Decode stored per-question snapshot ----------
    try {
        $snapshot = json_decode($row->students_answer ?? '[]', true) ?: [];
    } catch (\Throwable $e) {
        $snapshot = [];
    }

   $snapByQ = [];
foreach ($snapshot as $s) {
    if (isset($s['question_id'])) {
        $snapByQ[(int)$s['question_id']] = $this->normalizeSnap($s); // ← add normalizer
    }
}

    // ---------- 3. Load questions + answer options ----------
    $questions = DB::table('quizz_questions')
        ->where('quiz_id', $row->quiz_id)
        ->orderBy('question_order')
        ->get();

    $answerRows = DB::table('quizz_question_answers')
        ->whereIn('belongs_question_id', $questions->pluck('id'))
        ->orderBy('belongs_question_id')
        ->orderBy('answer_order')
        ->get()
        ->groupBy('belongs_question_id');

    // time_spent per question
    $timeRows = DB::table('quizz_attempt_answers')
        ->where('attempt_id', $row->attempt_id)
        ->get()
        ->keyBy('question_id');

    $questionPayload = [];
    $totalTimeUsed   = 0;

    foreach ($questions as $q) {
        $qid      = (int) $q->id;
        $ansRows  = $answerRows[$qid] ?? collect();
        $snap     = $snapByQ[$qid] ?? null;
        $timeRow  = $timeRows[$qid] ?? null;

        if ($timeRow) {
            $totalTimeUsed += (int) $timeRow->time_spent_sec;
        }

        // --- Compute correct_text for ALL types, including FIB ---
        $correctText = '';
        $qType = (string) ($q->question_type ?? '');

        if ($qType === 'fill_in_the_blank') {
            $selRaw = $snap['selected'] ?? null;
            $fib    = $this->fibExplain($q, $ansRows, $selRaw);
            $correctText = (string) ($fib['correct_display'] ?? '');
        } else {
            $correctLabels = $ansRows
                ->where('is_correct', 1)
                ->pluck('answer_title')
                ->values()
                ->all();
            $correctText = implode(', ', $correctLabels);
        }

        $questionPayload[] = [
            'question_id'         => $qid,
            'order'               => (int) $q->question_order,
            'title'               => $q->question_title,
            'description'         => $q->question_description,
            'type'                => $qType,
            'mark'                => (int) $q->question_mark,
            // 'time_spent_sec'      => $timeRow ? (int) $timeRow->time_spent_sec : 0,
            'is_correct'          => $snap ? (int) ($snap['is_correct'] ?? 0) : 0,
            'awarded_mark'        => $snap ? (int) ($snap['awarded_mark'] ?? 0) : 0,
            // New format — derive selected_answer_ids and selected_text from normalized snap
'selected_answer_ids' => $snap
    ? ($snap['answer_ids'] ?? (isset($snap['answer_id']) && $snap['answer_id'] !== null ? [$snap['answer_id']] : null))
    : null,
'selected_text'       => $snap
    ? ($snap['answer_text'] ?? null)
    : null,
'time_spent_sec'      => $snap
    ? (int)($snap['time_sec'] ?? ($timeRow?->time_spent_sec ?? 0))  // prefer snap, fallback to DB row
    : ($timeRow ? (int)$timeRow->time_spent_sec : 0),
            'correct_text'        => $correctText,
            'answers'             => $ansRows->map(function ($a) {
                return [
                    'answer_id'    => (int) $a->id,
                    'title'        => $a->answer_title,
                    'is_correct'   => (int) $a->is_correct,
                    'answer_order' => (int) ($a->answer_order ?? 0),
                ];
            })->values(),
        ];
    }

    // ---------- 4. OPTIONAL: also include student details from DB ----------
    $student = DB::table('users')
        ->where('id', (int) $row->user_id)
        ->whereNull('deleted_at')
        ->first(['id', 'name', 'email']);

    // ---------- 5. Response payload ----------
    return response()->json([
        'success'  => true,

        'quiz'     => [
            'id'               => (int) $row->quiz_id,
            'name'             => $row->quiz_name,
            'description'      => $row->quiz_description,
            'total_questions'  => (int) ($row->quiz_total_questions ?? $row->total_questions),
            'total_time'       => (int) ($row->total_time ?? 0), // minutes
        ],

        'attempt'  => [
            'attempt_id'         => (int) $row->attempt_id,
            'attempt_uuid'       => (string) $row->attempt_uuid,
            'status'             => (string) $row->attempt_status,
            'started_at'         => $row->started_at ? Carbon::parse($row->started_at)->toDateTimeString() : null,
            'finished_at'        => $row->finished_at ? Carbon::parse($row->finished_at)->toDateTimeString() : null,
            'total_time_sec'     => (int) $row->total_time_sec,
            'server_deadline_at' => $row->server_deadline_at ? Carbon::parse($row->server_deadline_at)->toDateTimeString() : null,
            'time_used_sec'      => $totalTimeUsed,
        ],

        'result'   => [
            'result_id'        => (int) $row->result_id,
            'result_uuid'      => (string) ($row->result_uuid ?? ''),
            'user_id'          => (int) $row->user_id,
            'marks_obtained'   => (int) $row->marks_obtained,
            'total_marks'      => (int) $row->total_marks,
            'percentage'       => $row->total_marks
                ? (float) round($row->marks_obtained / max(1, $row->total_marks) * 100, 2)
                : 0.0,
            'attempt_number'   => (int) ($row->attempt_number ?? 0),
            'total_questions'  => (int) $row->total_questions,
            'total_correct'    => (int) $row->total_correct,
            'total_incorrect'  => (int) $row->total_incorrect,
            'total_skipped'    => (int) $row->total_skipped,
            'publish_to_student' => (int) ($row->publish_to_student ?? 0),
            'result_created_at'  => $row->result_created_at
                ? Carbon::parse($row->result_created_at)->toDateTimeString()
                : null,
        ],

        'student' => $student ? [
            'id'    => (int) $student->id,
            'name'  => (string) $student->name,
            'email' => (string) $student->email,
        ] : null,

        'questions' => $questionPayload,
    ], 200);
}

     

     public function resultDetailForInstructor(Request $request, int $resultId)
     {
    
         $actor = $this->actor($request);
         $role  = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string)($actor['role'] ?? '')));
         $userId = (int)($actor['id'] ?? 0);
     
         if ($userId <= 0) {
             return response()->json([
                 'success' => false,
                 'message' => 'Unauthorized',
             ], 401);
         }
     
         // 2) Load result + attempt + quiz (+ student)
         $row = DB::table('quizz_results as r')
             ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
             ->join('quizz as q', 'q.id', '=', 'r.quiz_id')
             ->join('users as u', 'u.id', '=', 'r.user_id')
             ->where('r.id', $resultId)
             ->select([
                 'r.id as result_id',
                 'r.quiz_id',
                 'r.attempt_id',
                 'r.user_id',
                 'r.marks_obtained',
                 'r.total_marks',
                 'r.total_questions',
                 'r.total_correct',
                 'r.total_incorrect',
                 'r.total_skipped',
                 'r.percentage',
                 'r.attempt_number',
                 'r.students_answer',
                 'r.publish_to_student',
                 'r.result_set_up_type',
                 'r.result_release_date',
                 'r.created_at as result_created_at',
     
                 'a.uuid as attempt_uuid',
                 'a.status as attempt_status',
                 'a.started_at',
                 'a.finished_at',
                 'a.total_time_sec',
                 'a.server_deadline_at',
     
                 'q.quiz_name',
                 'q.quiz_description',
                 'q.total_time',
                 'q.total_questions as quiz_total_questions',
     
                 'u.name as student_name',
                 'u.email as student_email',
             ])
             ->first();
     
         if (!$row) {
             return response()->json([
                 'success' => false,
                 'message' => 'Result not found',
             ], 404);
         }
     
         // 3) Extra guard: instructor/examiner must be assigned to this quiz
         if (in_array($role, ['instructor','examiner'], true)) {
             $assigned = DB::table('user_quiz_assignments')
                 ->where('quiz_id', $row->quiz_id)
                 ->where('user_id', $userId)
                 ->whereNull('deleted_at')
                 ->where('status', 'active')
                 ->exists();
     
             if (!$assigned) {
                 return response()->json([
                     'success' => false,
                     'message' => 'You are not assigned to this quiz',
                 ], 403);
             }
         }
     
         // 4) Decode stored per-question snapshot
         try {
             $snapshot = json_decode($row->students_answer ?? '[]', true) ?: [];
         } catch (\Throwable $e) {
             $snapshot = [];
         }
         $snapByQ = [];
         foreach ($snapshot as $s) {
             if (isset($s['question_id'])) {
                 $snapByQ[(int)$s['question_id']] = $s;
             }
         }
     
         // 5) Load questions + answers
         $questions = DB::table('quizz_questions')
             ->where('quiz_id', $row->quiz_id)
             ->orderBy('question_order')
             ->get();
     
         $answerRows = DB::table('quizz_question_answers')
             ->whereIn('belongs_question_id', $questions->pluck('id'))
             ->orderBy('belongs_question_id')
             ->orderBy('answer_order')
             ->get()
             ->groupBy('belongs_question_id');
     
         // time_spent per question
         $timeRows = DB::table('quizz_attempt_answers')
             ->where('attempt_id', $row->attempt_id)
             ->get()
             ->keyBy('question_id');
     
         $questionPayload = [];
         $totalTimeUsed = 0;
     
         foreach ($questions as $q) {
             $qid     = (int)$q->id;
             $ansRows = $answerRows[$qid] ?? collect();
             $snap    = $snapByQ[$qid] ?? null;
             $timeRow = $timeRows[$qid] ?? null;
     
             if ($timeRow) {
                 $totalTimeUsed += (int)$timeRow->time_spent_sec;
             }
     
             // Build correct_text for MCQ + FIB
             $correctText = '';
             $qType = (string)($q->question_type ?? '');
     
             if ($qType === 'fill_in_the_blank') {
                 $selRaw = $snap['selected'] ?? null;
                 $fib    = $this->fibExplain($q, $ansRows, $selRaw);
                 $correctText = (string)($fib['correct_display'] ?? '');
             } else {
                 $correctLabels = $ansRows
                     ->where('is_correct', 1)
                     ->pluck('answer_title')
                     ->values()
                     ->all();
                 $correctText = implode(', ', $correctLabels);
             }
     
             $questionPayload[] = [
                 'question_id'         => $qid,
                 'order'               => (int)$q->question_order,
                 'title'               => $q->question_title,
                 'description'         => $q->question_description,
                 'type'                => $qType,
                 'mark'                => (int)$q->question_mark,
                 'time_spent_sec'      => $timeRow ? (int)$timeRow->time_spent_sec : 0,
                 'is_correct'          => $snap ? (int)($snap['is_correct'] ?? 0) : 0,
                 'awarded_mark'        => $snap ? (int)($snap['awarded_mark'] ?? 0) : 0,
                 'selected_answer_ids' => $snap['selected_answer_ids'] ?? null,
                 'selected_text'       => $snap['selected_text'] ?? null,
                 'correct_text'        => $correctText,
                 'answers'             => $ansRows->map(function ($a) {
                     return [
                         'answer_id'    => (int)$a->id,
                         'title'        => $a->answer_title,
                         'is_correct'   => (int)$a->is_correct,
                         'answer_order' => (int)($a->answer_order ?? 0),
                     ];
                 })->values(),
             ];
         }
     
         return response()->json([
             'success'  => true,
             'quiz'     => [
                 'id'               => (int)$row->quiz_id,
                 'name'             => $row->quiz_name,
                 'description'      => $row->quiz_description,
                 'total_questions'  => (int)($row->quiz_total_questions ?? $row->total_questions),
                 'total_time'       => (int)($row->total_time ?? 0),
             ],
             'attempt'  => [
                 'attempt_id'         => (int)$row->attempt_id,
                 'attempt_uuid'       => (string)$row->attempt_uuid,
                 'status'             => $row->attempt_status,
                 'started_at'         => $row->started_at
                                          ? Carbon::parse($row->started_at)->toDateTimeString()
                                          : null,
                 'finished_at'        => $row->finished_at
                                          ? Carbon::parse($row->finished_at)->toDateTimeString()
                                          : null,
                 'total_time_sec'     => (int)$row->total_time_sec,
                 'server_deadline_at' => $row->server_deadline_at
                                          ? Carbon::parse($row->server_deadline_at)->toDateTimeString()
                                          : null,
                 'time_used_sec'      => $totalTimeUsed,
             ],
             'result'   => [
                 'result_id'       => (int)$row->result_id,
                 'marks_obtained'  => (int)$row->marks_obtained,
                 'total_marks'     => (int)$row->total_marks,
                 'percentage'      => $row->total_marks
                     ? (float)round($row->marks_obtained / max(1, $row->total_marks) * 100, 2)
                     : 0.0,
                 'attempt_number'  => (int)($row->attempt_number ?? 0),
                 'total_questions' => (int)$row->total_questions,
                 'total_correct'   => (int)$row->total_correct,
                 'total_incorrect' => (int)$row->total_incorrect,
                 'total_skipped'   => (int)$row->total_skipped,
             ],
             'questions' => $questionPayload,
             'student'   => [
                 'id'    => (int)$row->user_id,
                 'name'  => (string)$row->student_name,
                 'email' => (string)$row->student_email,
             ],
         ], 200);
     }

    public function export(Request $request, int $resultId)
    {
        // Auth (same as resultDetail)
        $user = $this->getUserFromToken($request);
    
        // Load row & enforce ownership
        $row = DB::table('quizz_results as r')
            ->join('quizz as q', 'q.id', '=', 'r.quiz_id')
            ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
            ->where('r.id', $resultId)
            ->where('r.user_id', $user->id)
            ->select([
                'r.*',
                'a.started_at','a.finished_at','a.total_time_sec',
                'q.quiz_name','q.quiz_description','q.total_time'
            ])->first();
    
        if (!$row) {
            return response()->json(['success'=>false,'message'=>'Result not found'], 404);
        }
    
        // respect publish logic
        $allowViewNow = $this->shouldPublishToStudent((int)$row->quiz_id);
        if (!((int)$row->publish_to_student === 1 || $allowViewNow)) {
            return response()->json(['success'=>false,'message'=>'Result is not yet published for students'], 403);
        }
    
        // Load question snapshot for the document (optional/simple)
        $answers = json_decode($row->students_answer ?? '[]', true) ?: [];
        $questions = DB::table('quizz_questions')
            ->where('quiz_id', $row->quiz_id)
            ->orderBy('question_order')
            ->get();
        $answerRows = DB::table('quizz_question_answers')
            ->whereIn('belongs_question_id', $questions->pluck('id'))
            ->orderBy('belongs_question_id')->orderBy('answer_order')
            ->get()->groupBy('belongs_question_id');
    
        // Build a minimal normalized structure for export
        $mapSnap = [];
        foreach ($answers as $a) if (isset($a['question_id'])) $mapSnap[(int)$a['question_id']] = $a;
    
        $items = [];
        foreach ($questions as $q) {
            $qid = (int)$q->id;
            $snap = $mapSnap[$qid] ?? null;
            $corr = $answerRows[$qid] ?? collect();
            $correctLabels = $corr->where('is_correct',1)->pluck('answer_title')->values()->all();
            $items[] = [
                'no'         => (int)$q->question_order,
                'title'      => (string)$q->question_title,
                'mark'       => (int)$q->question_mark,
                'is_correct' => (int)($snap['is_correct'] ?? 0),
                'awarded'    => (int)($snap['awarded_mark'] ?? 0),
'your' => (function() use ($snap) {
    if (!$snap) return '';
    $snap = $this->normalizeSnap($snap);
    if (isset($snap['answer_text'])) return (string)$snap['answer_text'];
    if (isset($snap['answer_ids']))  return implode(', ', $snap['answer_ids']);
    if (isset($snap['answer_id']) && $snap['answer_id'] !== null) return (string)$snap['answer_id'];
    return '';
})(),                'correct'    => implode(', ', $correctLabels),
            ];
        }
    
        $format = strtolower($request->query('format','docx'));
        $safeName = 'exam_result_'.$resultId;
    
        if ($format === 'docx') {
            // Prefer PhpWord if available
            if (class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
                $phpWord  = new \PhpOffice\PhpWord\PhpWord();
                $section  = $phpWord->addSection();
                $fontH    = ['bold' => true, 'size' => 16];
                $fontB    = ['bold' => true];
                $fontM    = ['size' => 11];
    
                $section->addText('Exam Result', $fontH);
                $section->addTextBreak(1);
    
                $section->addText('Quiz: '.$row->quiz_name, $fontB);
                $section->addText('Student: '.($user->name ?? ('#'.$user->id)), $fontM);
                $section->addText('Score: '.$row->marks_obtained.' / '.$row->total_marks.'  ('.($row->total_marks ? round($row->marks_obtained/max(1,$row->total_marks)*100,2) : 0).'%)', $fontM);
                if ($row->started_at)  $section->addText('Started at: '.\Carbon\Carbon::parse($row->started_at)->toDayDateTimeString(), $fontM);
                if ($row->finished_at) $section->addText('Finished at: '.\Carbon\Carbon::parse($row->finished_at)->toDayDateTimeString(), $fontM);
                $section->addTextBreak(1);
    
                // Table
                $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'cccccc', 'cellMargin' => 60]);
                $table->addRow();
                foreach (['Q#','Question','Your Answer','Correct Answer','Marks'] as $col) {
                    $table->addCell(2000)->addText($col, ['bold'=>true]);
                }
                foreach ($items as $it) {
                    $table->addRow();
                    $table->addCell(800)->addText((string)$it['no']);
                    $table->addCell(7000)->addText(strip_tags((string)$it['title']));
                    $table->addCell(4000)->addText($it['your'] !== '' ? $it['your'] : '—');
                    $table->addCell(4000)->addText($it['correct'] !== '' ? $it['correct'] : '—');
                    $table->addCell(1800)->addText($it['awarded'].' / '.$it['mark']);
                }
    
                $tmp = tempnam(sys_get_temp_dir(), 'res_').'.docx';
                $phpWord->save($tmp, 'Word2007');
    
                return response()->download($tmp, $safeName.'.docx')->deleteFileAfterSend(true);
            }
    
            // Fallback: Word-compatible HTML (.doc)
            $html = view('exports.result_doc_fallback', [
                'row' => $row, 'user' => $user, 'items' => $items
            ])->render();
    
            return response($html, 200, [
                'Content-Type' => 'application/msword',
                'Content-Disposition' => 'attachment; filename="'.$safeName.'.doc"',
            ]);
        }
    
        // PDF: simple HTML fallback (users can “Save as PDF”)
        $html = view('exports.result_pdf_fallback', [
            'row' => $row, 'user' => $user, 'items' => $items
        ])->render();
    
        // If you use dompdf, replace the below with real PDF generation.
        return response($html, 200, [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'attachment; filename="'.$safeName.'.html"',
        ]);
    }

    public function assignedResultsForQuiz(Request $request, string $quizKey)
    {
        // ---------- 1. Role via CheckRole ----------
        if ($resp = $this->requireRole($request, ['instructor','examiner','admin','super_admin'])) {
            return $resp; // 403 if not allowed
        }
    
        $actor = $this->actor($request);
        $role  = (string) ($actor['role'] ?? '');
        $userId= (int) ($actor['id'] ?? 0);
    
        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
    
        // ---------- 2. Resolve quiz (id | uuid) ----------
        $quiz = $this->quizByKey($quizKey);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found',
            ], 404);
        }
    
        // ---------- 3. Ensure this quiz is assigned to this instructor/examiner ----------
        // Admin / super_admin can see all; instructors/examiners must be assigned via user_quiz_assignments
        $normalizedRole = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '', $role));
    
        if (in_array($normalizedRole, ['instructor','examiner'], true)) {
            $assigned = DB::table('user_quiz_assignments')
                ->where('quiz_id', $quiz->id)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->exists();
    
            if (!$assigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this quiz',
                ], 403);
            }
        }
    
        // ---------- 4. Filters: search, pagination, sorting ----------
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $qText   = trim((string) $request->query('q', ''));
        $sort    = (string) $request->query('sort', '-result_created_at'); 
        // allowed: student_name, percentage, marks_obtained, result_created_at
    
        $dir = 'asc';
        $col = $sort;
        if (str_starts_with($sort, '-')) {
            $dir = 'desc';
            $col = ltrim($sort, '-');
        }
    
        $sortMap = [
            'student_name'      => 'u.name',
            'percentage'        => 'r.percentage',
            'marks_obtained'    => 'r.marks_obtained',
            'result_created_at' => 'r.created_at',
        ];
        if (!isset($sortMap[$col])) {
            $col = 'result_created_at';
            $dir = 'desc';
        }
        $orderByCol = $sortMap[$col];
    
        // ---------- 5. Base query: all results for this quiz ----------
        $q = DB::table('quizz_results as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->join('quizz_attempts as a', 'a.id', '=', 'r.attempt_id')
            ->where('r.quiz_id', $quiz->id)
            ->whereNull('u.deleted_at');
    
        if ($qText !== '') {
            $q->where(function ($w) use ($qText) {
                $w->where('u.name', 'like', "%{$qText}%")
                  ->orWhere('u.email', 'like', "%{$qText}%");
            });
        }
    
        $total = (clone $q)->count('r.id');
    
        $rows = $q->orderBy($orderByCol, $dir)
            ->orderBy('r.id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->select([
                'r.id as result_id',
                'r.marks_obtained',
                'r.total_marks',
                'r.total_questions',
                'r.total_correct',
                'r.total_incorrect',
                'r.total_skipped',
                'r.percentage',
                'r.attempt_number',
                'r.publish_to_student',
                'r.created_at as result_created_at',
    
                'u.id   as student_id',
                'u.name as student_name',
                'u.email as student_email',
    
                'a.id   as attempt_id',
                'a.uuid as attempt_uuid',
                'a.status as attempt_status',
                'a.started_at',
                'a.finished_at',
                'a.total_time_sec',
            ])
            ->get();
    
        // ---------- 6. Optional: time_used_sec per attempt ----------
        $timeMap = [];
        if ($rows->count() > 0) {
            $timeMap = DB::table('quizz_attempt_answers')
                ->whereIn('attempt_id', $rows->pluck('attempt_id'))
                ->groupBy('attempt_id')
                ->select('attempt_id', DB::raw('SUM(time_spent_sec) as total_time'))
                ->pluck('total_time', 'attempt_id');
        }
    
        $attempts = $rows->map(function ($r) use ($timeMap) {
            $timeUsed = isset($timeMap[$r->attempt_id]) ? (int) $timeMap[$r->attempt_id] : 0;
    
            return [
                'result_id'        => (int) $r->result_id,
                'attempt_id'       => (int) $r->attempt_id,
                'attempt_uuid'     => (string) $r->attempt_uuid,
                'attempt_status'   => (string) $r->attempt_status,
    
                'student_id'       => (int) $r->student_id,
                'student_name'     => (string) $r->student_name,
                'student_email'    => (string) $r->student_email,
    
                'marks_obtained'   => (int) $r->marks_obtained,
                'total_marks'      => (int) $r->total_marks,
                'percentage'       => (float) $r->percentage,
                'total_questions'  => (int) $r->total_questions,
                'total_correct'    => (int) $r->total_correct,
                'total_incorrect'  => (int) $r->total_incorrect,
                'total_skipped'    => (int) $r->total_skipped,
                'attempt_number'   => (int) $r->attempt_number,
                'publish_to_student'=> (int) $r->publish_to_student,
    
                'started_at'       => $r->started_at
                                        ? Carbon::parse($r->started_at)->toDateTimeString()
                                        : null,
                'finished_at'      => $r->finished_at
                                        ? Carbon::parse($r->finished_at)->toDateTimeString()
                                        : null,
                'total_time_sec'   => (int) ($r->total_time_sec ?? 0),
                'time_used_sec'    => $timeUsed,
    
                'result_created_at'=> Carbon::parse($r->result_created_at)->toDateTimeString(),
            ];
        })->values();
    
        // ---------- 7. Aggregate stats for analysis (top card on UI) ----------
        $agg = DB::table('quizz_results')
            ->where('quiz_id', $quiz->id)
            ->selectRaw('
                COUNT(*)                           as total_attempts,
                COUNT(DISTINCT user_id)            as unique_students,
                MAX(percentage)                    as max_percentage,
                MIN(percentage)                    as min_percentage,
                AVG(percentage)                    as avg_percentage
            ')
            ->first();
    
        return response()->json([
            'success' => true,
            'quiz'    => [
                'id'            => (int) $quiz->id,
                'uuid'          => (string) $quiz->uuid,
                'name'          => (string) ($quiz->quiz_name ?? 'Quiz'),
                'description'   => (string) ($quiz->quiz_description ?? ''),
                'total_time'    => (int) ($quiz->total_time ?? 0), // minutes
            ],
            'stats'   => [
                'total_attempts'   => (int) ($agg->total_attempts ?? 0),
                'unique_students'  => (int) ($agg->unique_students ?? 0),
                'max_percentage'   => $agg->max_percentage !== null ? (float)$agg->max_percentage : null,
                'min_percentage'   => $agg->min_percentage !== null ? (float)$agg->min_percentage : null,
                'avg_percentage'   => $agg->avg_percentage !== null ? round((float)$agg->avg_percentage, 2) : null,
            ],
            'attempts' => $attempts,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ], 200);
    }
}
