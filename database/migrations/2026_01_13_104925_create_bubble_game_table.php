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
        Schema::create('bubble_game', function (Blueprint $table) {
            /* =========================
             * Primary / Identifiers
             * ========================= */
            $table->bigIncrements('id');            // PK, AUTO_INCREMENT
            $table->char('uuid', 36)->unique();     // External UUID (CHAR(36))

            /* =========================
             * Core
             * ========================= */
            $table->string('title', 180);
            $table->text('description')->nullable();

            /* =========================
             * Settings
             * ========================= */
            $table->unsignedInteger('max_attempts')->default(1);
            $table->unsignedInteger('per_question_time_sec')->default(30);

            $table->enum('is_question_random', ['yes', 'no'])->default('no');
            $table->enum('is_bubble_positions_random', ['yes', 'no'])->default('yes');
            $table->enum('allow_skip', ['yes', 'no'])->default('no');

            $table->integer('points_correct')->default(1);
            $table->integer('points_wrong')->default(0);

            $table->enum('show_solution_after', ['never', 'after_each', 'after_finish'])
                  ->default('after_finish');

            $table->mediumText('instructions_html')->nullable();

            /* =========================
             * Meta / Status
             * ========================= */
            $table->string('status', 20)->default('active'); // active/inactive
            $table->json('metadata')->nullable();

            /* =========================
             * Audit
             * ========================= */
            $table->unsignedBigInteger('created_by')->nullable(); // FK -> users.id

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->string('created_at_ip', 45)->nullable();
            $table->string('updated_at_ip', 45)->nullable();

            $table->softDeletes(); // deleted_at (indexed)

            /* =========================
             * Indexes / FK
             * ========================= */
            $table->index('status');
            $table->index('created_by');

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bubble_game');
    }
};
