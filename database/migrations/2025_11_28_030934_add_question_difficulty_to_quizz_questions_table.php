<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quizz_questions', function (Blueprint $table) {
            // If the column might already exist in this project, guard with hasColumn
            if (!Schema::hasColumn('quizz_questions', 'question_difficulty')) {
                $table->enum('question_difficulty', ['easy', 'medium', 'hard'])
                      ->default('medium')
                      ->after('question_mark');
            }
        });

        // Defensive: ensure existing rows have a value
        if (Schema::hasColumn('quizz_questions', 'question_difficulty')) {
            DB::table('quizz_questions')
                ->whereNull('question_difficulty')
                ->update(['question_difficulty' => 'medium']);
        }
    }

    public function down(): void
    {
        Schema::table('quizz_questions', function (Blueprint $table) {
            if (Schema::hasColumn('quizz_questions', 'question_difficulty')) {
                $table->dropColumn('question_difficulty');
            }
        });
    }
};
