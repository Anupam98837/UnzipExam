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
        Schema::create('bubble_game_results', function (Blueprint $table) {
            /* =========================
             * Primary / Identifiers
             * ========================= */
            $table->bigIncrements('id');        // PK, AUTO_INCREMENT
            $table->char('uuid', 36)->unique(); // External UUID

            /* =========================
             * Relations
             * ========================= */
            $table->unsignedBigInteger('bubble_game_id'); // FK -> bubble_game.id
            $table->unsignedBigInteger('user_id');        // FK -> users.id

            $table->index('bubble_game_id');
            $table->index('user_id');

            /* =========================
             * Attempt / Answers / Score
             * ========================= */
            $table->unsignedInteger('attempt_no')->default(1);
            $table->json('user_answer_json')->nullable(); // per question answers (your example JSON)
            $table->integer('score')->default(0);

            /* =========================
             * Timestamps
             * ========================= */
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            /* =========================
             * IP + Soft delete
             * ========================= */
            $table->string('created_at_ip', 45)->nullable();
            $table->string('updated_at_ip', 45)->nullable();

            $table->softDeletes(); // deleted_at (indexed)

            /* =========================
             * FKs
             * =========================
             * NOTE: Parent table assumed to be `bubble_game` (singular)
             * If yours is `bubble_games`, change on('bubble_game') -> on('bubble_games')
             */
            $table->foreign('bubble_game_id')
                  ->references('id')->on('bubble_game')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            /* =========================
             * Helpful indexes
             * ========================= */
            $table->index(['bubble_game_id', 'user_id', 'attempt_no'], 'bgr_game_user_attempt_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bubble_game_results');
    }
};
