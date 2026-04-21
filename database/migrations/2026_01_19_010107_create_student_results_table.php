<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_results', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            // ✅ who owns the result (student)
            $table->unsignedBigInteger('user_id')->index();

            // ✅ one of these will be filled
            $table->unsignedBigInteger('door_game_result_id')->nullable()->unique();
            $table->unsignedBigInteger('bubble_game_result_id')->nullable()->unique();
            $table->unsignedBigInteger('quizz_result_id')->nullable()->unique();

            $table->timestamps();

            // ✅ FK: user
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            // ✅ FK: result tables
            $table->foreign('door_game_result_id')
                ->references('id')->on('door_game_results')
                ->cascadeOnDelete();

            $table->foreign('bubble_game_result_id')
                ->references('id')->on('bubble_game_results')
                ->cascadeOnDelete();

            $table->foreign('quizz_result_id')
                ->references('id')->on('quizz_results')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
