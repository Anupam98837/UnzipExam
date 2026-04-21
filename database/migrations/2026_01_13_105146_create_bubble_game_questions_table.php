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
        Schema::create('bubble_game_questions', function (Blueprint $table) {
            /* =========================
             * Primary / Identifiers
             * ========================= */
            $table->bigIncrements('id');            // PK, AUTO_INCREMENT
            $table->char('uuid', 36)->unique();     // External UUID

            /* =========================
             * Relations
             * ========================= */
            $table->unsignedBigInteger('bubble_game_id'); // FK -> bubble_game.id
            $table->index('bubble_game_id');

            /* =========================
             * Question fields
             * ========================= */
            $table->string('title', 255)->nullable(); // Optional prompt
            $table->enum('select_type', ['ascending', 'descending'])->default('ascending');

            $table->json('bubbles_json');                     // Bubble items shown
            $table->json('answer_sequence_json')->nullable(); // Correct tap order
            $table->json('answer_value_json')->nullable();    // Optional evaluated values / result

            $table->unsignedInteger('bubbles_count')->default(3);
            $table->integer('points')->default(1);            // Override points (optional)
            $table->unsignedInteger('order_no')->default(0);  // Question ordering
            $table->enum('status', ['active', 'inactive'])->default('active');

            /* =========================
             * Timestamps
             * ========================= */
            $table->timestamps();

            /* =========================
             * FK
             * =========================
             * NOTE:
             * Your parent table was created as `bubble_game` (singular) earlier.
             * If your actual parent table is `bubble_games`, change `on('bubble_game')` to `on('bubble_games')`.
             */
            $table->foreign('bubble_game_id')
                  ->references('id')->on('bubble_game')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            /* =========================
             * Helpful indexes
             * ========================= */
            $table->index(['bubble_game_id', 'status']);
            $table->index(['bubble_game_id', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bubble_game_questions');
    }
};
