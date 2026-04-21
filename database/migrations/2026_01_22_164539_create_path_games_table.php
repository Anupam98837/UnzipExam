<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('path_games', function (Blueprint $table) {
            $table->bigIncrements('id');

            // External UUID for API/frontend
            $table->char('uuid', 36)->unique();

            // Game core info
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->mediumText('instructions_html')->nullable();

            // Solution timing policy
            $table->enum('show_solution_after', ['never', 'after_each', 'after_finish'])
                  ->default('after_finish');

            // Grid setup
            $table->tinyInteger('grid_dim')->unsigned()->default(3); // 3 => 3x3 => 9 tiles
            $table->json('grid_json'); // full structure of 9 tiles + rules

            // Rules
            $table->unsignedInteger('time_limit_sec')->default(30);
            $table->unsignedInteger('max_attempts')->default(1);

            // Rotation settings
            $table->boolean('rotation_enabled')->default(true);
            $table->enum('rotation_mode', ['cw', 'ccw', 'both'])->default('both');

            // Status
            $table->string('status', 20)->default('active'); // active/inactive

            // Audit
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            // FK
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('path_games');
    }
};
