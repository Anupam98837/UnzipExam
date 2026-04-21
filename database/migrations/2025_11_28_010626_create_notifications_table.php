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
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary Key

            // Core fields
            $table->string('title', 255);
            $table->text('message');

            // JSON fields
            // For Unzip Exam, you can store exam-specific receivers:
            // e.g. [{ "id": 12, "role": "student", "read": 0 }]
            $table->json('receivers')->nullable();

            // Extra context: exam_id, batch_id, quiz_id, route name, etc.
            $table->json('metadata')->nullable();

            // Type / category
            // e.g. system, exam, quiz, result, alert, maintenance, etc.
            $table->string('type', 50)->default('general');

            // Redirect URL or route (front-end /panel link)
            $table->string('link_url', 255)->nullable();

            // Priority and status enums
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                  ->default('normal');

            $table->enum('status', ['active', 'archived', 'deleted'])
                  ->default('active');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')
                  ->useCurrent()
                  ->useCurrentOnUpdate();

            // Indexes for performance
            $table->index(['type']);
            $table->index(['priority']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
