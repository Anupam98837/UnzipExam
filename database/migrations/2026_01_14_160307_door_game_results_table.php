<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('door_game_results', function (Blueprint $table) {
            $table->bigIncrements('id');                 // PK, AUTO_INCREMENT
            $table->char('uuid', 36)->unique();          // External UUID

            // Relations
            $table->unsignedBigInteger('door_game_id')->index(); // FK -> door_games.id
            $table->unsignedBigInteger('user_id')->index();      // FK -> users.id

            // Attempt / answers / scoring
            $table->unsignedInteger('attempt_no')->default(1);
            $table->json('user_answer_json')->nullable(); // path + events + timing
            $table->integer('score')->default(0);
            $table->unsignedInteger('time_taken_ms')->nullable()->index(); // fastest ranking

            $table->enum('status', ['win','fail','timeout','in_progress'])
                  ->default('in_progress')
                  ->index();

            // Audit (IP)
            $table->string('created_at_ip', 45)->nullable();
            $table->string('updated_at_ip', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');

            // FKs
            $table->foreign('door_game_id')
                  ->references('id')->on('door_game')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('door_game_results');
    }
};
