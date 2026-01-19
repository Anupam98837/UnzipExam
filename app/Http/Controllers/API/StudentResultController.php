<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentResultController extends Controller
{
    private function actor(Request $request): array
    {
        return [
            'role' => $request->attributes->get('auth_role'),
            'type' => $request->attributes->get('auth_tokenable_type'),
            'id'   => (int) ($request->attributes->get('auth_tokenable_id') ?? 0),
        ];
    }

    private function firstExistingTable(array $candidates): ?string
    {
        foreach ($candidates as $t) {
            if (Schema::hasTable($t)) return $t;
        }
        return null;
    }

    private function safeCol(?string $table, string $alias, string $col, string $fallbackSql = "NULL"): string
    {
        if ($table && Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
            return "{$alias}.{$col}";
        }
        return $fallbackSql;
    }

    private function safeRCol(string $table, string $alias, string $col, string $fallbackSql = "NULL"): string
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $col)) {
            return "{$alias}.{$col}";
        }
        return $fallbackSql;
    }

    private function buildQueries(int $userId): array
    {
        $queries = [];

        // ✅ master tables (your project uses singular mostly)
        $doorGameTable   = $this->firstExistingTable(['door_game', 'door_games']);
        $bubbleGameTable = $this->firstExistingTable(['bubble_game', 'bubble_games']);
        $quizTable       = $this->firstExistingTable(['quizz', 'quizzes', 'quiz']);

        // ==========================
        // ✅ Door Game Results
        // ==========================
        if (Schema::hasTable('door_game_results')) {

            $hasPub = Schema::hasColumn('door_game_results', 'publish_to_student');

            $door = DB::table('door_game_results as r')
                ->join('users as u', 'u.id', '=', 'r.user_id');

            if ($doorGameTable) {
                $door->leftJoin($doorGameTable . ' as g', 'g.id', '=', 'r.door_game_id');
            }

            $door->selectRaw("
                'door_game' as module,
                " . ($doorGameTable ? "g.id" : "r.door_game_id") . " as game_id,
                " . $this->safeCol($doorGameTable, 'g', 'uuid', "NULL") . " as game_uuid,
                " . $this->safeCol($doorGameTable, 'g', 'title', "CONCAT('Door Game #', r.door_game_id)") . " as game_title,

                r.id as result_id,
                r.uuid as result_uuid,

                " . $this->safeRCol('door_game_results', 'r', 'attempt_no', "1") . " as attempt_no,
                " . $this->safeRCol('door_game_results', 'r', 'score', "0") . " as score,
                " . $this->safeRCol('door_game_results', 'r', 'accuracy', "NULL") . " as accuracy,

                " . ($hasPub ? "r.publish_to_student" : "0") . " as publish_to_student,
                COALESCE(" . $this->safeRCol('door_game_results', 'r', 'result_created_at', "NULL") . ", r.created_at) as result_created_at,

                u.id as student_id,
                " . (Schema::hasColumn('users','uuid')  ? "u.uuid"  : "NULL") . " as student_uuid,
                " . (Schema::hasColumn('users','name')  ? "u.name"  : "NULL") . " as student_name,
                " . (Schema::hasColumn('users','email') ? "u.email" : "NULL") . " as student_email
            ");

            $door->where('r.user_id', $userId);

            // ✅ Only published
            if ($hasPub) $door->where('r.publish_to_student', 1);
            else $door->whereRaw("0=1");

            $queries[] = $door;
        }

        // ==========================
        // ✅ Bubble Game Results
        // ==========================
        if (Schema::hasTable('bubble_game_results')) {

            $hasPub = Schema::hasColumn('bubble_game_results', 'publish_to_student');

            $bubble = DB::table('bubble_game_results as r')
                ->join('users as u', 'u.id', '=', 'r.user_id');

            if ($bubbleGameTable) {
                $bubble->leftJoin($bubbleGameTable . ' as g', 'g.id', '=', 'r.bubble_game_id');
            }

            $bubble->selectRaw("
                'bubble_game' as module,
                " . ($bubbleGameTable ? "g.id" : "r.bubble_game_id") . " as game_id,
                " . $this->safeCol($bubbleGameTable, 'g', 'uuid', "NULL") . " as game_uuid,
                " . $this->safeCol($bubbleGameTable, 'g', 'title', "CONCAT('Bubble Game #', r.bubble_game_id)") . " as game_title,

                r.id as result_id,
                r.uuid as result_uuid,

                " . $this->safeRCol('bubble_game_results', 'r', 'attempt_no', "1") . " as attempt_no,
                " . $this->safeRCol('bubble_game_results', 'r', 'score', "0") . " as score,
                " . $this->safeRCol('bubble_game_results', 'r', 'accuracy', "NULL") . " as accuracy,

                " . ($hasPub ? "r.publish_to_student" : "0") . " as publish_to_student,
                COALESCE(" . $this->safeRCol('bubble_game_results', 'r', 'result_created_at', "NULL") . ", r.created_at) as result_created_at,

                u.id as student_id,
                " . (Schema::hasColumn('users','uuid')  ? "u.uuid"  : "NULL") . " as student_uuid,
                " . (Schema::hasColumn('users','name')  ? "u.name"  : "NULL") . " as student_name,
                " . (Schema::hasColumn('users','email') ? "u.email" : "NULL") . " as student_email
            ");

            $bubble->where('r.user_id', $userId);

            // ✅ Only published
            if ($hasPub) $bubble->where('r.publish_to_student', 1);
            else $bubble->whereRaw("0=1");

            $queries[] = $bubble;
        }

        // ==========================
        // ✅ Quizz Results
        // ==========================
        if (Schema::hasTable('quizz_results')) {

            $hasPub = Schema::hasColumn('quizz_results', 'publish_to_student');

            $quizz = DB::table('quizz_results as r')
                ->join('users as u', 'u.id', '=', 'r.user_id');

            // ✅ join quiz master
            if ($quizTable && Schema::hasColumn('quizz_results', 'quiz_id')) {
                $quizz->leftJoin($quizTable . ' as g', 'g.id', '=', 'r.quiz_id');
            }

            $quizz->selectRaw("
                'quizz' as module,
                " . ($quizTable ? "g.id" : "r.quiz_id") . " as game_id,
                " . $this->safeCol($quizTable, 'g', 'uuid', $this->safeRCol('quizz_results','r','quiz_uuid',"NULL")) . " as game_uuid,
                " . $this->safeCol($quizTable, 'g', 'title', "CONCAT('Quizz #', r.quiz_id)") . " as game_title,

                r.id as result_id,
                r.uuid as result_uuid,

                " . $this->safeRCol('quizz_results', 'r', 'attempt_id', "1") . " as attempt_no,
                " . $this->safeRCol('quizz_results', 'r', 'marks_obtained', "0") . " as score,
                " . $this->safeRCol('quizz_results', 'r', 'percentage', "NULL") . " as accuracy,

                " . ($hasPub ? "r.publish_to_student" : "0") . " as publish_to_student,
                COALESCE(" . $this->safeRCol('quizz_results', 'r', 'released_at', "NULL") . ", r.created_at) as result_created_at,

                u.id as student_id,
                " . (Schema::hasColumn('users','uuid')  ? "u.uuid"  : "NULL") . " as student_uuid,
                " . (Schema::hasColumn('users','name')  ? "u.name"  : "NULL") . " as student_name,
                " . (Schema::hasColumn('users','email') ? "u.email" : "NULL") . " as student_email
            ");

            $quizz->where('r.user_id', $userId);

            // ✅ Only published
            if ($hasPub) $quizz->where('r.publish_to_student', 1);
            else $quizz->whereRaw("0=1");

            $queries[] = $quizz;
        }

        return $queries;
    }

    // ✅ GET: only my published results (student side)
    public function myPublished(Request $request)
    {
        $actor  = $this->actor($request);
        $userId = (int) ($actor['id'] ?? 0);

        if ($userId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve user from token.'
            ], 403);
        }

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(10, (int) $request->query('per_page', 20)));
        $search  = trim((string) $request->query('q', ''));

        $queries = $this->buildQueries($userId);

        if (count($queries) === 0) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => $perPage,
                    'page' => $page,
                    'total_pages' => 1
                ]
            ]);
        }

        // ✅ UNION ALL
        $base = array_shift($queries);
        foreach ($queries as $qq) {
            $base->unionAll($qq);
        }

        $outer = DB::query()->fromSub($base, 'x');

        // ✅ search only by game title (no folder anymore)
        if ($search !== '') {
            $outer->where('x.game_title', 'like', "%{$search}%");
        }

        $outer->orderByDesc('x.result_created_at');

        // ✅ Count query
        $queries2 = $this->buildQueries($userId);
        $base2 = array_shift($queries2);
        foreach ($queries2 as $qq) {
            $base2->unionAll($qq);
        }

        $countQ = DB::query()->fromSub($base2, 'x');
        if ($search !== '') {
            $countQ->where('x.game_title', 'like', "%{$search}%");
        }

        $total = (int) $countQ->count();
        $items = $outer->forPage($page, $perPage)->get();

        $data = $items->map(function ($x) {
            $rid = $x->result_uuid;

            // ✅ view_url mapping
            $viewUrl = '#';
            if ($x->module === 'door_game')   $viewUrl = "/decision-making-test/results/{$rid}/view";
            if ($x->module === 'quizz')       $viewUrl = "/exam/results/{$rid}/view";
            if ($x->module === 'bubble_game') $viewUrl = "/test/results/{$rid}/view";

            return [
                'module' => $x->module,
                'view_url' => $viewUrl,

                'student' => [
                    'id'    => (int) $x->student_id,
                    'uuid'  => $x->student_uuid,
                    'name'  => $x->student_name,
                    'email' => $x->student_email,
                ],

                'game' => [
                    'id'    => (int) ($x->game_id ?? 0),
                    'uuid'  => $x->game_uuid,
                    'title' => $x->game_title,
                ],

                'result' => [
                    'id' => (int) $x->result_id,
                    'uuid' => $x->result_uuid,
                    'attempt_no' => (int) ($x->attempt_no ?? 0),
                    'score' => $x->score,
                    'accuracy' => $x->accuracy,
                    'publish_to_student' => (int) $x->publish_to_student,
                    'result_created_at' => $x->result_created_at,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'page' => $page,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }
}
