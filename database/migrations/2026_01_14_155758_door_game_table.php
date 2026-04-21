<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('door_game', function (Blueprint $table) {
            $table->bigIncrements('id');                 // PK, AUTO_INCREMENT
            $table->char('uuid', 36)->unique();          // External UUID

            $table->string('title', 180);                // Game title
            $table->text('description')->nullable();     // Optional description
            $table->mediumText('instructions_html')->nullable(); // Rules / instructions (HTML allowed)

            $table->enum('show_solution_after', ['never','after_each','after_finish'])
                  ->default('after_finish');

            // 1=1×1, 2=2×2, 3=3×3 ...
            $table->tinyInteger('grid_dim')->unsigned()->default(3);

            // Cells data (should contain grid_dim*grid_dim items)
            $table->json('grid_json');

            $table->unsignedInteger('max_attempts')->default(1); // Attempts per user
            $table->unsignedInteger('time_limit_sec')->default(30); // Time limit per round/game

            $table->string('status', 20)->default('active')->index(); // active/inactive

            // Created/updated by (nullable)
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();

            $table->timestamps();

            $table->softDeletes();         // deleted_at
            $table->index('deleted_at');   // explicit index as you asked
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('door_game');
    }
};
