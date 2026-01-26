<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('path_game_results', function (Blueprint $table) {
            $table->bigIncrements('id');

            // External UUID
            $table->char('uuid', 36)->unique();

            // Parent Game (FK)
            $table->unsignedBigInteger('path_game_id')->index();

            // Player (FK)
            $table->unsignedBigInteger('user_id')->index();

            // Attempt number
            $table->unsignedInteger('attempt_no')->default(1);

            // User answer JSON (path + rotations + timing + final arrows)
            $table->json('user_answer_json')->nullable();

            // Score after evaluation
            $table->integer('score')->default(0);

            // Total time in ms
            $table->unsignedInteger('time_taken_ms')->nullable()->index();

            // Attempt status
            $table->enum('status', ['win', 'fail', 'timeout', 'in_progress'])
                  ->default('in_progress')
                  ->index();

            // Meta
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->string('created_at_ip', 45)->nullable();
            $table->string('updated_at_ip', 45)->nullable();

            // Soft delete
            $table->softDeletes();

            // ✅ Foreign Keys
            // NOTE: Change table name if your game table is `path_game` instead of `path_games`
            $table->foreign('path_game_id')
                  ->references('id')
                  ->on('path_games')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('path_game_results');
    }
};
