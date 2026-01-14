<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bubble_game_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identity
            $table->char('uuid', 36)->unique();

            // Relations
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('bubble_game_id')->index();

            // 10-character uppercase alphanumeric code
            $table->string('assignment_code', 10)->unique();

            // Status + audit
            $table->enum('status', ['active', 'revoked'])
                  ->default('active')
                  ->index();

            $table->unsignedBigInteger('assigned_by')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FKs (optional but recommended)
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();

            // ✅ If your bubble games table name is `bubble_game` (singular),
            // change `bubble_games` to `bubble_game` below.
            $table->foreign('bubble_game_id')
                  ->references('id')->on('bubble_game')
                  ->cascadeOnDelete();

            $table->foreign('assigned_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();

            // no duplicate (user, bubble_game)
            $table->unique(['user_id', 'bubble_game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bubble_game_assignments');
    }
};
