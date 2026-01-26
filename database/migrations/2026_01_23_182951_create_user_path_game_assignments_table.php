<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_path_game_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();

            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('path_game_id')->index();

            $table->string('assignment_code', 120)->nullable()->index();
            $table->enum('status', ['active','revoked'])->default('active')->index();

            $table->unsignedBigInteger('assigned_by')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');

            // ✅ Foreign keys
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('path_game_id')
                  ->references('id')->on('path_games')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            // ✅ Optional safety: prevent duplicate assignment rows per user/game
            $table->unique(['user_id', 'path_game_id'], 'upg_user_game_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_path_game_assignments');
    }
};
