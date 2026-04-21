<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuizzTransferController extends Controller
{
    private function actor(Request $request): array
    {
        return [
            'role' => (string)($request->attributes->get('auth_role') ?? ''),
            'type' => (string)($request->attributes->get('auth_tokenable_type') ?? ''),
            'id'   => (int)($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    private function requireAuth(Request $request)
    {
        $a = $this->actor($request);
        if (($a['id'] ?? 0) <= 0) {
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }
        return null;
    }

    private function roleNorm(?string $role): string
    {
        $r = strtolower(trim((string)$role));
        $r = str_replace([' ', '-'], '_', $r);
        $r = preg_replace('/_+/', '_', $r) ?? $r;
        return $r;
    }

    private function isFacultyLike(string $role): bool
    {
        return in_array($this->roleNorm($role), [
            'faculty', 'hod', 'instructor', 'professor',
            'associate_professor', 'assistant_professor', 'lecturer',
        ], true);
    }

    private function isAdminAllScope(Request $request): bool
    {
        return in_array($this->roleNorm($this->actor($request)['role'] ?? ''), ['admin', 'super_admin'], true);
    }

    private function isAssignedToQuiz(int $userId, int $quizId): bool
    {
        if ($userId <= 0 || $quizId <= 0) return false;

        return DB::table('user_quiz_assignments')
            ->where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->exists();
    }

    private function canAccessQuizRow(Request $request, $row): bool
    {
        if (!$row) return false;

        $actor = $this->actor($request);
        $role = $this->roleNorm($actor['role'] ?? '');

        if ($this->isAdminAllScope($request)) return true;
        if ($this->isFacultyLike($role)) return true;
        if ($role === 'examiner') {
            return $this->isAssignedToQuiz((int)($actor['id'] ?? 0), (int)$row->id);
        }

        return false;
    }

    private function findQuizAnyByKey(string $key)
    {
        $q = DB::table('quizz')->whereNull('deleted_at');
        if (ctype_digit($key)) $q->where('id', (int)$key);
        else $q->where('uuid', $key);
        return $q->first();
    }

    private function decodeMaybeJson($value)
    {
        if ($value === null || $value === '') return null;
        if (is_array($value)) return $value;
        $decoded = json_decode((string)$value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function nextImportedQuizName(string $baseName): string
    {
        $baseName = trim($baseName);
        if ($baseName === '') $baseName = 'Untitled Quiz';

        $exists = DB::table('quizz')
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(quiz_name) = ?', [mb_strtolower($baseName)])
            ->exists();

        if (!$exists) return $baseName;

        $copyName = $baseName . ' copy';
        $copyExists = DB::table('quizz')
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(quiz_name) = ?', [mb_strtolower($copyName)])
            ->exists();

        if (!$copyExists) return $copyName;

        $i = 1;
        while (true) {
            $candidate = $baseName . ' copy ' . $i;
            $dup = DB::table('quizz')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(quiz_name) = ?', [mb_strtolower($candidate)])
                ->exists();

            if (!$dup) return $candidate;
            $i++;
        }
    }

    public function export(Request $request, string $key)
    {
        if ($resp = $this->requireAuth($request)) return $resp;

        $quiz = $this->findQuizAnyByKey($key);
        if (!$quiz) {
            return response()->json(['success' => false, 'message' => 'Quiz not found'], 404);
        }

        if (!$this->canAccessQuizRow($request, $quiz)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 403);
        }

        $questions = DB::table('quizz_questions')
            ->where('quiz_id', (int)$quiz->id)
            ->orderBy('question_order')
            ->orderBy('id')
            ->get();

        $answersByQuestion = [];
        if ($questions->isNotEmpty()) {
            $answerRows = DB::table('quizz_question_answers')
                ->whereIn('belongs_question_id', $questions->pluck('id')->all())
                ->orderBy('belongs_question_id')
                ->orderBy('answer_order')
                ->orderBy('id')
                ->get();

            foreach ($answerRows as $ans) {
                $answersByQuestion[(int)$ans->belongs_question_id][] = [
                    'answer_title'          => $ans->answer_title,
                    'is_correct'            => (bool)$ans->is_correct,
                    'answer_order'          => (int)($ans->answer_order ?? 0),
                    'belongs_question_type' => $ans->belongs_question_type,
                    'image_id'              => $ans->image_id !== null ? (int)$ans->image_id : null,
                    'answer_two_gap_match'  => $ans->answer_two_gap_match,
                    'answer_view_format'    => $ans->answer_view_format,
                    'answer_settings'       => $this->decodeMaybeJson($ans->answer_settings),
                ];
            }
        }

        $payloadQuestions = [];
        foreach ($questions as $question) {
            $payloadQuestions[] = [
                'question_title'       => $question->question_title,
                'question_description' => $question->question_description,
                'answer_explanation'   => $question->answer_explanation,
                'question_type'        => $question->question_type,
                'question_mark'        => (int)($question->question_mark ?? 1),
                'question_difficulty'  => $question->question_difficulty ?: 'medium',
                'question_settings'    => $this->decodeMaybeJson($question->question_settings),
                'question_order'       => (int)($question->question_order ?? 1),
                'group_title'          => $question->group_title,
                'answers'              => $answersByQuestion[(int)$question->id] ?? [],
            ];
        }

        $package = [
            'type'        => 'college_management_quiz_package',
            'version'     => 1,
            'exported_at' => now()->toIso8601String(),
            'quiz'        => [
                'quiz_name'           => $quiz->quiz_name,
                'quiz_description'    => $quiz->quiz_description,
                'quiz_img'            => $quiz->quiz_img,
                'instructions'        => $quiz->instructions,
                'note'                => $quiz->note,
                'is_public'           => $quiz->is_public,
                'result_set_up_type'  => $quiz->result_set_up_type,
                'result_release_date' => $quiz->result_release_date,
                'total_time'          => $quiz->total_time !== null ? (int)$quiz->total_time : null,
                'total_attempts'      => $quiz->total_attempts !== null ? (int)$quiz->total_attempts : 1,
                'total_questions'     => $quiz->total_questions !== null ? (int)$quiz->total_questions : count($payloadQuestions),
                'is_question_random'  => $quiz->is_question_random ?? 'no',
                'is_option_random'    => $quiz->is_option_random ?? 'no',
                'status'              => $quiz->status ?? 'active',
                'metadata'            => $this->decodeMaybeJson($quiz->metadata),
            ],
            'questions'   => $payloadQuestions,
        ];

        $filename = Str::slug((string)$quiz->quiz_name ?: 'quiz') . '-export.json';

        return response()->streamDownload(function () use ($package) {
            echo json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        if ($resp = $this->requireAuth($request)) return $resp;
        if (!$this->isAdminAllScope($request)) {
            return response()->json(['success' => false, 'message' => 'Only admin can import quiz packages'], 403);
        }

        $payload = null;
        if ($request->hasFile('file')) {
            $raw = file_get_contents($request->file('file')->getRealPath());
            $payload = json_decode((string)$raw, true);
        } elseif ($request->filled('package')) {
            $pkg = $request->input('package');
            $payload = is_array($pkg) ? $pkg : json_decode((string)$pkg, true);
        } elseif (is_array($request->input('quiz')) || is_array($request->all())) {
            $payload = $request->all();
        }

        if (!is_array($payload)) {
            return response()->json(['success' => false, 'message' => 'Invalid quiz package'], 422);
        }

        $quizData = is_array($payload['quiz'] ?? null) ? $payload['quiz'] : null;
        $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : null;

        if (!$quizData || $questions === null) {
            return response()->json(['success' => false, 'message' => 'Quiz package must include quiz and questions'], 422);
        }

        $sourceName = trim((string)($quizData['quiz_name'] ?? 'Untitled Quiz'));
        $newName = $this->nextImportedQuizName($sourceName);
        $actor = $this->actor($request);
        $now = now();

        DB::beginTransaction();
        try {
            $quizUuid = (string)Str::uuid();
            $insertQuiz = [
                'created_by'          => (int)($actor['id'] ?: 0),
                'uuid'                => $quizUuid,
                'quiz_name'           => $newName,
                'quiz_description'    => $quizData['quiz_description'] ?? null,
                'quiz_img'            => $quizData['quiz_img'] ?? null,
                'instructions'        => $quizData['instructions'] ?? null,
                'note'                => $quizData['note'] ?? null,
                'is_public'           => in_array(($quizData['is_public'] ?? 'no'), ['yes', 'no'], true) ? $quizData['is_public'] : 'no',
                'result_set_up_type'  => in_array(($quizData['result_set_up_type'] ?? 'Immediately'), ['Immediately', 'Now', 'Schedule'], true) ? $quizData['result_set_up_type'] : 'Immediately',
                'result_release_date' => $quizData['result_release_date'] ?? null,
                'total_time'          => isset($quizData['total_time']) && $quizData['total_time'] !== '' ? (int)$quizData['total_time'] : null,
                'total_attempts'      => isset($quizData['total_attempts']) && (int)$quizData['total_attempts'] > 0 ? (int)$quizData['total_attempts'] : 1,
                'total_questions'     => count($questions),
                'is_question_random'  => in_array(($quizData['is_question_random'] ?? 'no'), ['yes', 'no'], true) ? $quizData['is_question_random'] : 'no',
                'is_option_random'    => in_array(($quizData['is_option_random'] ?? 'no'), ['yes', 'no'], true) ? $quizData['is_option_random'] : 'no',
                'status'              => in_array(($quizData['status'] ?? 'active'), ['active', 'archived'], true) ? $quizData['status'] : 'active',
                'metadata'            => json_encode($quizData['metadata'] ?? new \stdClass(), JSON_UNESCAPED_UNICODE),
                'created_at_ip'       => $request->ip(),
                'created_at'          => $now,
                'updated_at'          => $now,
                'deleted_at'          => null,
            ];

            $quizId = DB::table('quizz')->insertGetId($insertQuiz);

            foreach ($questions as $index => $question) {
                $questionUuid = (string)Str::uuid();
                $questionType = (string)($question['question_type'] ?? 'mcq');
                if (!in_array($questionType, ['mcq', 'true_false', 'fill_in_the_blank'], true)) {
                    $questionType = 'mcq';
                }

                $questionId = DB::table('quizz_questions')->insertGetId([
                    'uuid'                 => $questionUuid,
                    'quiz_id'              => $quizId,
                    'quiz_uuid'            => $quizUuid,
                    'created_by'           => (int)($actor['id'] ?: 0),
                    'updated_by'           => (int)($actor['id'] ?: 0),
                    'created_at_ip'        => $request->ip(),
                    'question_order'       => isset($question['question_order']) ? (int)$question['question_order'] : ($index + 1),
                    'group_title'          => $question['group_title'] ?? null,
                    'question_title'       => (string)($question['question_title'] ?? ''),
                    'question_description' => $question['question_description'] ?? null,
                    'answer_explanation'   => $question['answer_explanation'] ?? null,
                    'question_type'        => $questionType,
                    'question_mark'        => isset($question['question_mark']) ? (int)$question['question_mark'] : 1,
                    'question_difficulty'  => in_array(($question['question_difficulty'] ?? 'medium'), ['easy', 'medium', 'hard'], true) ? $question['question_difficulty'] : 'medium',
                    'question_settings'    => isset($question['question_settings']) ? json_encode($question['question_settings'], JSON_UNESCAPED_UNICODE) : null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);

                $answers = is_array($question['answers'] ?? null) ? $question['answers'] : [];
                if (!empty($answers)) {
                    $rows = [];
                    foreach ($answers as $aIndex => $answer) {
                        $rows[] = [
                            'uuid'                  => (string)Str::uuid(),
                            'belongs_question_id'   => $questionId,
                            'belongs_question_uuid' => $questionUuid,
                            'created_by'            => (int)($actor['id'] ?: 0),
                            'updated_by'            => (int)($actor['id'] ?: 0),
                            'created_at_ip'         => $request->ip(),
                            'belongs_question_type' => $answer['belongs_question_type'] ?? $questionType,
                            'answer_title'          => $answer['answer_title'] ?? null,
                            'is_correct'            => !empty($answer['is_correct']) ? 1 : 0,
                            'image_id'              => isset($answer['image_id']) && $answer['image_id'] !== '' ? (int)$answer['image_id'] : null,
                            'answer_two_gap_match'  => $answer['answer_two_gap_match'] ?? null,
                            'answer_view_format'    => $answer['answer_view_format'] ?? null,
                            'answer_settings'       => isset($answer['answer_settings']) ? json_encode($answer['answer_settings'], JSON_UNESCAPED_UNICODE) : null,
                            'answer_order'          => isset($answer['answer_order']) ? (int)$answer['answer_order'] : $aIndex,
                            'created_at'            => $now,
                            'updated_at'            => $now,
                        ];
                    }
                    DB::table('quizz_question_answers')->insert($rows);
                }
            }

            DB::table('quizz')->where('id', $quizId)->update([
                'total_questions' => (int)DB::table('quizz_questions')->where('quiz_id', $quizId)->count(),
                'updated_at'      => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quiz imported successfully',
                'data'    => [
                    'id'              => $quizId,
                    'uuid'            => $quizUuid,
                    'quiz_name'       => $newName,
                    'question_count'  => count($questions),
                    'source_quiz_name'=> $sourceName,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to import quiz package',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
