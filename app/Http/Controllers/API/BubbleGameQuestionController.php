<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BubbleGameQuestionController extends Controller
{
    /**
     * Display a listing of questions for a specific bubble game.
     */
    public function index(Request $request, string $gameUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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
                    'from' => $offset + 1,
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
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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
    public function show(string $gameUuid, string $questionUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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
        $updateData = ['updated_at' => now()];

        // Handle each field individually
        if (isset($data['title'])) {
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

        if (isset($data['points'])) {
            $updateData['points'] = $data['points'];
        }

        if (isset($data['order_no'])) {
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
    public function destroy(string $gameUuid, string $questionUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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

        DB::table('bubble_game_questions')
            ->where('uuid', $questionUuid)
            ->delete();

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
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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

        foreach ($request->input('questions') as $questionData) {
            DB::table('bubble_game_questions')
                ->where('uuid', $questionData['uuid'])
                ->where('bubble_game_id', $bubbleGame->id)
                ->update([
                    'order_no' => $questionData['order_no'],
                    'updated_at' => now()
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Questions reordered successfully'
        ]);
    }

    /**
     * Duplicate a question.
     */
    public function duplicate(string $gameUuid, string $questionUuid)
    {
        $bubbleGame = DB::table('bubble_game')
            ->where('uuid', $gameUuid)
            ->whereNull('deleted_at')
            ->first();

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
            'order_no' => $maxOrderNo + 1,
            'status' => $original->status,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $duplicateId = DB::table('bubble_game_questions')->insertGetId($duplicateData);

        // Get created duplicate
        $duplicate = DB::table('bubble_game_questions')
            ->where('id', $duplicateId)
            ->first();

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