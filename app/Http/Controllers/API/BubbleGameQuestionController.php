<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BubbleGameQuestionController extends Controller
{
    /* =========================
     * Auth/Role + Activity Log
     * ========================= */

    /** Actor pulled from CheckRole middleware */
    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    /**
     * Keep logs small: don’t dump big json blobs into old/new values.
     * Store lengths + md5 for json fields instead.
     */
    private function questionSnapshotForLog($row): ?array
    {
        if (!$row) return null;

        $arr = (array) $row;

        foreach (['bubbles_json','answer_sequence_json','answer_value_json'] as $k) {
            if (array_key_exists($k, $arr)) {
                $val = (string) ($arr[$k] ?? '');
                $arr[$k.'_len'] = $val !== '' ? strlen($val) : 0;
                $arr[$k.'_md5'] = $val !== '' ? md5($val) : null;
                unset($arr[$k]);
            }
        }

        return $arr;
    }

    /**
     * Insert row into user_data_activity_log using DB facade.
     * Columns expected:
     * performed_by, performed_by_role, ip, user_agent, activity, module, table_name,
     * record_id, changed_fields (json), old_values (json), new_values (json),
     * log_note, created_at, updated_at
     */
    private function logActivity(
        Request $request,
        string $activity,                 // 'store'|'update'|'destroy'
        string $module,                   // 'BubbleGameQuestions'
        string $note,                     // human-readable note
        string $tableName,                // 'bubble_game_questions'
        ?int $recordId = null,
        ?array $changed = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $a = $this->actor($request);

        $changedFields = null;
        if (is_array($changed)) {
            $changedFields = array_values(array_unique(
                array_keys($changed) === range(0, count($changed)-1) ? $changed : array_keys($changed)
            ));
        }

        try {
            DB::table('user_data_activity_log')->insert([
                'performed_by'      => $a['id'] ?: 0,
                'performed_by_role' => $a['role'] ?: null,
                'ip'                => $request->ip(),
                'user_agent'        => (string) $request->userAgent(),
                'activity'          => $activity,
                'module'            => $module,
                'table_name'        => $tableName ?: 'unknown',
                'record_id'         => $recordId,
                'changed_fields'    => $changedFields ? json_encode($changedFields, JSON_UNESCAPED_UNICODE) : null,
                'old_values'        => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values'        => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'log_note'          => $note,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('user_data_activity_log insert failed', ['error' => $e->getMessage()]);
        }
    }

    /* =========================
     * Notification helpers (DB-only)
     * ========================= */

    /** Insert one notification row (DB-only). */
    private function persistNotification(array $payload): void
    {
        try {
            $title     = (string)($payload['title']    ?? 'Notification');
            $message   = (string)($payload['message']  ?? '');
            $receivers = array_values(array_map(function($x){
                return [
                    'id'   => isset($x['id']) ? (int)$x['id'] : null,
                    'role' => (string)($x['role'] ?? 'unknown'),
                    'read' => (int)($x['read'] ?? 0),
                ];
            }, $payload['receivers'] ?? []));

            $metadata = $payload['metadata'] ?? [];
            $type     = (string)($payload['type'] ?? 'general');
            $linkUrl  = $payload['link_url'] ?? null;
            $priority = in_array(($payload['priority'] ?? 'normal'), ['low','normal','high','urgent'], true)
                        ? $payload['priority'] : 'normal';
            $status   = in_array(($payload['status'] ?? 'active'), ['active','archived','deleted'], true)
                        ? $payload['status'] : 'active';

            DB::table('notifications')->insert([
                'title'      => $title,
                'message'    => $message,
                'receivers'  => json_encode($receivers, JSON_UNESCAPED_UNICODE),
                'metadata'   => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                'type'       => $type,
                'link_url'   => $linkUrl,
                'priority'   => $priority,
                'status'     => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('notifications insert failed', ['error' => $e->getMessage()]);
        }
    }

    /** Admin receivers: all admins (id, role=admin). */
    private function adminReceivers(array $excludeIds = []): array
    {
        $exclude = array_flip(array_map('intval', $excludeIds));
        $rows = DB::table('users')->select('id')->get();
        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r->id;
            if (!isset($exclude[$id])) $out[] = ['id'=>$id, 'role'=>'admin', 'read'=>0];
        }
        return $out;
    }

    /* =========================
     * Core fetch helper
     * ========================= */

    private function findGameOr404(string $gameUuid)
    {
        return DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();
    }

    /* =========================
     * Endpoints
     * ========================= */

    /**
     * Display a listing of questions for a specific bubble game.
     */
    public function index(Request $request, string $gameUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $query = DB::table('bubble_game_questions')
            ->where('bubble_game_id', $bubbleGame->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Order by
        $orderBy = $request->get('order_by', 'order_no');
        $orderDir = $request->get('order_dir', 'asc');
        $query->orderBy($orderBy, $orderDir);

        // Paginate or get all
        if ($request->get('paginate', true)) {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            $total = $query->count();
            $questions = $query->offset($offset)->limit($perPage)->get();

            // Decode JSON fields
            foreach ($questions as $question) {
                $question->bubbles_json = json_decode($question->bubbles_json);
                $question->answer_sequence_json = json_decode($question->answer_sequence_json);
                $question->answer_value_json = json_decode($question->answer_value_json);
            }

            return response()->json([
                'success' => true,
                'data' => $questions,
                'game' => $bubbleGame,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage),
                    'from' => $total ? $offset + 1 : 0,
                    'to' => min($offset + $perPage, $total)
                ]
            ]);
        } else {
            $questions = $query->get();

            // Decode JSON fields
            foreach ($questions as $question) {
                $question->bubbles_json = json_decode($question->bubbles_json);
                $question->answer_sequence_json = json_decode($question->answer_sequence_json);
                $question->answer_value_json = json_decode($question->answer_value_json);
            }

            return response()->json([
                'success' => true,
                'data' => $questions,
                'game' => $bubbleGame
            ]);
        }
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request, string $gameUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'select_type' => ['required', Rule::in(['ascending', 'descending'])],
            'bubbles_json' => 'required|array|min:1',
            'bubbles_json.*.label' => 'required|string',
            'bubbles_json.*.value' => 'nullable',
            'answer_sequence_json' => 'nullable|array',
            'answer_value_json' => 'nullable|array',
            'bubbles_count' => 'nullable|integer|min:1',
            'points' => 'nullable|integer',
            'order_no' => 'nullable|integer|min:0',
            'status' => ['nullable', Rule::in(['active', 'inactive'])]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Auto-increment order_no if not provided
        if (!isset($data['order_no'])) {
            $maxOrderNo = DB::table('bubble_game_questions')
                ->where('bubble_game_id', $bubbleGame->id)
                ->max('order_no');
            $data['order_no'] = $maxOrderNo ? $maxOrderNo + 1 : 1;
        }

        // Prepare insert data
        $insertData = [
            'uuid' => (string) Str::uuid(),
            'bubble_game_id' => $bubbleGame->id,
            'title' => $data['title'] ?? null,
            'select_type' => $data['select_type'],
            'bubbles_json' => json_encode($data['bubbles_json']),
            'answer_sequence_json' => isset($data['answer_sequence_json']) ? json_encode($data['answer_sequence_json']) : null,
            'answer_value_json' => isset($data['answer_value_json']) ? json_encode($data['answer_value_json']) : null,
            'bubbles_count' => count($data['bubbles_json']),
            'points' => $data['points'] ?? 1,
            'order_no' => $data['order_no'],
            'status' => $data['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now()
        ];

        $questionId = DB::table('bubble_game_questions')->insertGetId($insertData);

        // Get created question
        $question = DB::table('bubble_game_questions')
            ->where('id', $questionId)
            ->first();

        // ✅ Activity Log
        $this->logActivity(
            $request,
            'store',
            'BubbleGameQuestions',
            'Created bubble game question',
            'bubble_game_questions',
            (int)$questionId,
            array_keys($insertData),
            null,
            $this->questionSnapshotForLog($question)
        );

        // ✅ Notify Admins
        $appUrl = rtrim((string) config('app.url'), '/');
        $this->persistNotification([
            'title'     => 'Bubble question created',
            'message'   => 'A bubble game question was created for game: '.$gameUuid,
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'         => 'created',
                'game_uuid'      => $gameUuid,
                'bubble_game_id' => (int) $bubbleGame->id,
                'question_id'    => (int) $questionId,
                'question_uuid'  => $question ? (string) $question->uuid : null,
                'actor'          => $this->actor($request),
            ],
            'type'      => 'bubble_game_question',
            'link_url'  => $appUrl.'/bubble-games/'.$gameUuid.'/questions',
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        // Decode JSON for response
        $question->bubbles_json = json_decode($question->bubbles_json);
        $question->answer_sequence_json = json_decode($question->answer_sequence_json);
        $question->answer_value_json = json_decode($question->answer_value_json);

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'data' => $question
        ], 201);
    }

    /**
     * Display the specified question.
     */
    public function show(Request $request, string $gameUuid, string $questionUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $question = DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->where('bubble_game_id', $bubbleGame->id)
            ->first();

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found'
            ], 404);
        }

        $question->bubbles_json = json_decode($question->bubbles_json);
        $question->answer_sequence_json = json_decode($question->answer_sequence_json);
        $question->answer_value_json = json_decode($question->answer_value_json);

        return response()->json([
            'success' => true,
            'data' => $question
        ]);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(Request $request, string $gameUuid, string $questionUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $question = DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->where('bubble_game_id', $bubbleGame->id)
            ->first();

        if (!$question) {
            // ✅ Log not-found attempt
            $this->logActivity(
                $request,
                'update',
                'BubbleGameQuestions',
                'Bubble question not found (update)',
                'bubble_game_questions',
                null,
                null,
                null,
                ['game_uuid'=>$gameUuid,'question_uuid'=>$questionUuid]
            );

            return response()->json([
                'success' => false,
                'message' => 'Question not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'select_type' => ['sometimes', Rule::in(['ascending', 'descending'])],
            'bubbles_json' => 'sometimes|array|min:1',
            'bubbles_json.*.label' => 'required_with:bubbles_json|string',
            'bubbles_json.*.value' => 'nullable',
            'answer_sequence_json' => 'nullable|array',
            'answer_value_json' => 'nullable|array',
            'bubbles_count' => 'nullable|integer|min:1',
            'points' => 'nullable|integer',
            'order_no' => 'nullable|integer|min:0',
            'status' => ['nullable', Rule::in(['active', 'inactive'])]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $before = $question;

        $updateData = ['updated_at' => now()];

        // Handle each field individually
        if (array_key_exists('title', $data)) {
            $updateData['title'] = $data['title'];
        }
        if (isset($data['select_type'])) {
            $updateData['select_type'] = $data['select_type'];
        }
        if (isset($data['bubbles_json'])) {
            $updateData['bubbles_json'] = json_encode($data['bubbles_json']);
            $updateData['bubbles_count'] = count($data['bubbles_json']);
        }
        if (isset($data['answer_sequence_json'])) {
            $updateData['answer_sequence_json'] = json_encode($data['answer_sequence_json']);
        }
        if (isset($data['answer_value_json'])) {
            $updateData['answer_value_json'] = json_encode($data['answer_value_json']);
        }
        if (array_key_exists('points', $data)) {
            $updateData['points'] = $data['points'];
        }
        if (array_key_exists('order_no', $data)) {
            $updateData['order_no'] = $data['order_no'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->update($updateData);

        // Get updated question
        $updatedQuestion = DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->first();

        // ✅ Activity Log
        $this->logActivity(
            $request,
            'update',
            'BubbleGameQuestions',
            'Updated bubble game question',
            'bubble_game_questions',
            (int) $updatedQuestion->id,
            array_keys($updateData),
            $this->questionSnapshotForLog($before),
            $this->questionSnapshotForLog($updatedQuestion)
        );

        // ✅ Notify Admins (exclude updated_at from message fields)
        $changed = array_values(array_diff(array_keys($updateData), ['updated_at']));
        $appUrl = rtrim((string) config('app.url'), '/');

        $this->persistNotification([
            'title'     => 'Bubble question updated',
            'message'   => $changed ? ('Updated fields: '.implode(', ', $changed)) : 'Bubble question updated.',
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'         => 'updated',
                'game_uuid'      => $gameUuid,
                'bubble_game_id' => (int) $bubbleGame->id,
                'question_uuid'  => $questionUuid,
                'question_id'    => (int) $updatedQuestion->id,
                'changed'        => $changed,
                'actor'          => $this->actor($request),
            ],
            'type'      => 'bubble_game_question',
            'link_url'  => $appUrl.'/bubble-games/'.$gameUuid.'/questions',
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        // Decode JSON for response
        $updatedQuestion->bubbles_json = json_decode($updatedQuestion->bubbles_json);
        $updatedQuestion->answer_sequence_json = json_decode($updatedQuestion->answer_sequence_json);
        $updatedQuestion->answer_value_json = json_decode($updatedQuestion->answer_value_json);

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'data' => $updatedQuestion
        ]);
    }

    /**
     * Remove the specified question from storage.
     */
    public function destroy(Request $request, string $gameUuid, string $questionUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $question = DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->where('bubble_game_id', $bubbleGame->id)
            ->first();

        if (!$question) {
            // ✅ Log not-found attempt
            $this->logActivity(
                $request,
                'destroy',
                'BubbleGameQuestions',
                'Bubble question not found (delete)',
                'bubble_game_questions',
                null,
                null,
                null,
                ['game_uuid'=>$gameUuid,'question_uuid'=>$questionUuid]
            );

            return response()->json([
                'success' => false,
                'message' => 'Question not found'
            ], 404);
        }

        DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->delete();

        // ✅ Activity Log (snapshot in old_values)
        $this->logActivity(
            $request,
            'destroy',
            'BubbleGameQuestions',
            'Deleted bubble game question',
            'bubble_game_questions',
            (int) $question->id,
            ['id','uuid','title','select_type','bubbles_count','points','order_no','status'],
            $this->questionSnapshotForLog($question),
            null
        );

        // ✅ Notify Admins
        $this->persistNotification([
            'title'     => 'Bubble question deleted',
            'message'   => 'A bubble game question was deleted for game: '.$gameUuid,
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'         => 'deleted',
                'game_uuid'      => $gameUuid,
                'bubble_game_id' => (int) $bubbleGame->id,
                'question_id'    => (int) $question->id,
                'question_uuid'  => (string) $question->uuid,
                'actor'          => $this->actor($request),
            ],
            'type'      => 'bubble_game_question',
            'link_url'  => null,
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully'
        ]);
    }

    /**
     * Bulk create questions for a game.
     */
    public function bulkStore(Request $request, string $gameUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.title' => 'nullable|string|max:255',
            'questions.*.select_type' => ['required', Rule::in(['ascending', 'descending'])],
            'questions.*.bubbles_json' => 'required|array|min:1',
            'questions.*.answer_sequence_json' => 'nullable|array',
            'questions.*.answer_value_json' => 'nullable|array',
            'questions.*.points' => 'nullable|integer',
            'questions.*.status' => ['nullable', Rule::in(['active', 'inactive'])]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $questionsData = $request->input('questions');
        $createdQuestions = [];

        $maxOrderNo = DB::table('bubble_game_questions')
            ->where('bubble_game_id', $bubbleGame->id)
            ->max('order_no') ?? 0;

        foreach ($questionsData as $index => $questionData) {
            $insertData = [
                'uuid' => (string) Str::uuid(),
                'bubble_game_id' => $bubbleGame->id,
                'title' => $questionData['title'] ?? null,
                'select_type' => $questionData['select_type'],
                'bubbles_json' => json_encode($questionData['bubbles_json']),
                'answer_sequence_json' => isset($questionData['answer_sequence_json']) ? json_encode($questionData['answer_sequence_json']) : null,
                'answer_value_json' => isset($questionData['answer_value_json']) ? json_encode($questionData['answer_value_json']) : null,
                'bubbles_count' => count($questionData['bubbles_json']),
                'points' => $questionData['points'] ?? 1,
                'order_no' => $maxOrderNo + $index + 1,
                'status' => $questionData['status'] ?? 'active',
                'created_at' => now(),
                'updated_at' => now()
            ];

            $questionId = DB::table('bubble_game_questions')->insertGetId($insertData);

            $createdQuestion = DB::table('bubble_game_questions')
                ->where('id', $questionId)
                ->first();

            $createdQuestion->bubbles_json = json_decode($createdQuestion->bubbles_json);
            $createdQuestion->answer_sequence_json = json_decode($createdQuestion->answer_sequence_json);
            $createdQuestion->answer_value_json = json_decode($createdQuestion->answer_value_json);

            $createdQuestions[] = $createdQuestion;
        }

        // ✅ Activity Log (summary)
        $createdSummary = array_map(function($q){
            return [
                'id'   => (int) $q->id,
                'uuid' => (string) $q->uuid,
                'title'=> $q->title,
                'order_no' => $q->order_no,
                'status'   => $q->status,
            ];
        }, $createdQuestions);

        $this->logActivity(
            $request,
            'store',
            'BubbleGameQuestions',
            'Bulk created bubble game questions',
            'bubble_game_questions',
            (int) $bubbleGame->id,              // record_id as game id (bulk op)
            ['bulk'],
            null,
            [
                'game_uuid' => $gameUuid,
                'count'     => count($createdQuestions),
                'items'     => $createdSummary
            ]
        );

        // ✅ Notify Admins
        $appUrl = rtrim((string) config('app.url'), '/');

        $this->persistNotification([
            'title'     => 'Bubble questions bulk created',
            'message'   => count($createdQuestions).' bubble questions created for game: '.$gameUuid,
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'         => 'bulk_created',
                'game_uuid'      => $gameUuid,
                'bubble_game_id' => (int) $bubbleGame->id,
                'count'          => count($createdQuestions),
                'actor'          => $this->actor($request),
            ],
            'type'      => 'bubble_game_question',
            'link_url'  => $appUrl.'/bubble-games/'.$gameUuid.'/questions',
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => count($createdQuestions) . ' questions created successfully',
            'data' => $createdQuestions
        ], 201);
    }

    /**
     * Reorder questions.
     */
    public function reorder(Request $request, string $gameUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'questions' => 'required|array|min:1',
            'questions.*.uuid' => 'required|string|exists:bubble_game_questions,uuid',
            'questions.*.order_no' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $payload = $request->input('questions');

        $uuids = array_values(array_unique(array_map(fn($x) => (string)($x['uuid'] ?? ''), $payload)));
        $beforeOrders = DB::table('bubble_game_questions')
            ->select('id','uuid','order_no')
            ->where('bubble_game_id', $bubbleGame->id)
            ->whereIn('uuid', $uuids)
            ->get()
            ->map(fn($r) => ['id'=>(int)$r->id,'uuid'=>(string)$r->uuid,'order_no'=>(int)$r->order_no])
            ->values()
            ->all();

        foreach ($payload as $questionData) {
            DB::table('bubble_game_questions')
                ->where('uuid', $questionData['uuid'])
                ->where('bubble_game_id', $bubbleGame->id)
                ->update([
                    'order_no' => $questionData['order_no'],
                    'updated_at' => now()
                ]);
        }

        $afterOrders = DB::table('bubble_game_questions')
            ->select('id','uuid','order_no')
            ->where('bubble_game_id', $bubbleGame->id)
            ->whereIn('uuid', $uuids)
            ->get()
            ->map(fn($r) => ['id'=>(int)$r->id,'uuid'=>(string)$r->uuid,'order_no'=>(int)$r->order_no])
            ->values()
            ->all();

        // ✅ Activity Log (bulk update)
        $this->logActivity(
            $request,
            'update',
            'BubbleGameQuestions',
            'Reordered bubble game questions',
            'bubble_game_questions',
            (int) $bubbleGame->id, // record_id as game id
            ['order_no'],
            ['game_uuid'=>$gameUuid,'before'=>$beforeOrders],
            ['game_uuid'=>$gameUuid,'after'=>$afterOrders]
        );

        // ✅ Notify Admins
        $appUrl = rtrim((string) config('app.url'), '/');
        $this->persistNotification([
            'title'     => 'Bubble questions reordered',
            'message'   => 'Questions reordered for game: '.$gameUuid,
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'         => 'reordered',
                'game_uuid'      => $gameUuid,
                'bubble_game_id' => (int) $bubbleGame->id,
                'count'          => count($payload),
                'actor'          => $this->actor($request),
            ],
            'type'      => 'bubble_game_question',
            'link_url'  => $appUrl.'/bubble-games/'.$gameUuid.'/questions',
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Questions reordered successfully'
        ]);
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(Request $request, string $gameUuid, string $questionUuid)
    {
        $bubbleGame = $this->findGameOr404($gameUuid);

        if (!$bubbleGame) {
            return response()->json([
                'success' => false,
                'message' => 'Bubble game not found'
            ], 404);
        }

        $original = DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->where('bubble_game_id', $bubbleGame->id)
            ->first();

        if (!$original) {
            // ✅ Log not-found attempt
            $this->logActivity(
                $request,
                'store',
                'BubbleGameQuestions',
                'Original bubble question not found (duplicate)',
                'bubble_game_questions',
                null,
                null,
                null,
                ['game_uuid'=>$gameUuid,'question_uuid'=>$questionUuid]
            );

            return response()->json([
                'success' => false,
                'message' => 'Question not found'
            ], 404);
        }

        $maxOrderNo = DB::table('bubble_game_questions')
            ->where('bubble_game_id', $bubbleGame->id)
            ->max('order_no');

        // Prepare duplicate data
        $duplicateData = [
            'uuid' => (string) Str::uuid(),
            'bubble_game_id' => $original->bubble_game_id,
            'title' => $original->title ? $original->title . ' (Copy)' : null,
            'select_type' => $original->select_type,
            'bubbles_json' => $original->bubbles_json,
            'answer_sequence_json' => $original->answer_sequence_json,
            'answer_value_json' => $original->answer_value_json,
            'bubbles_count' => $original->bubbles_count,
            'points' => $original->points,
            'order_no' => ($maxOrderNo ?? 0) + 1,
            'status' => $original->status,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $duplicateId = DB::table('bubble_game_questions')->insertGetId($duplicateData);

        // Get created duplicate
        $duplicate = DB::table('bubble_game_questions')
            ->where('id', $duplicateId)
            ->first();

        // ✅ Activity Log
        $this->logActivity(
            $request,
            'store',
            'BubbleGameQuestions',
            'Duplicated bubble game question',
            'bubble_game_questions',
            (int) $duplicateId,
            array_keys($duplicateData),
            $this->questionSnapshotForLog($original),
            $this->questionSnapshotForLog($duplicate)
        );

        // ✅ Notify Admins
        $appUrl = rtrim((string) config('app.url'), '/');
        $this->persistNotification([
            'title'     => 'Bubble question duplicated',
            'message'   => 'A bubble question was duplicated for game: '.$gameUuid,
            'receivers' => $this->adminReceivers(),
            'metadata'  => [
                'action'          => 'duplicated',
                'game_uuid'       => $gameUuid,
                'bubble_game_id'  => (int) $bubbleGame->id,
                'original_uuid'   => (string) $original->uuid,
                'duplicate_id'    => (int) $duplicateId,
                'duplicate_uuid'  => $duplicate ? (string) $duplicate->uuid : null,
                'actor'           => $this->actor($request),
            ],
            'type'      => 'bubble_game_question',
            'link_url'  => $appUrl.'/bubble-games/'.$gameUuid.'/questions',
            'priority'  => 'normal',
            'status'    => 'active',
        ]);

        // Decode JSON fields for response
        $duplicate->bubbles_json = json_decode($duplicate->bubbles_json);
        $duplicate->answer_sequence_json = json_decode($duplicate->answer_sequence_json);
        $duplicate->answer_value_json = json_decode($duplicate->answer_value_json);

        return response()->json([
            'success' => true,
            'message' => 'Question duplicated successfully',
            'data' => $duplicate
        ], 201);
    }
}
