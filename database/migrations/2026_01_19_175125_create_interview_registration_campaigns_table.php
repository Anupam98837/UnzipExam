<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ✅ Interview Registration Campaigns
     * URL rule (no need to store):  /register/{uuid}
     */
    public function up(): void
    {
        Schema::create('interview_registration_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');

            // External identifier for public URL
            $table->uuid('uuid')->unique();

            // ✅ FK -> user_folders.id (particular folder)
            $table->unsignedBigInteger('user_folder_id');

            // Campaign info
            $table->string('title', 180);          // campaign title
            $table->text('description')->nullable(); // campaign desc

            // Campaign window
            $table->dateTime('start_date');
            $table->dateTime('end_date');

            // Optional status
            $table->string('status', 20)->default('active'); // active/inactive

            // Optional metadata (future)
            $table->json('metadata')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable(); // FK -> users.id
            $table->string('created_at_ip', 45)->nullable();
            $table->string('updated_at_ip', 45)->nullable();

            // Timestamps + soft delete
            $table->timestamps();
            $table->softDeletes()->index();

            // Indexes
            $table->index('user_folder_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('created_by');

            // Foreign keys
            $table->foreign('user_folder_id')
                ->references('id')->on('user_folders')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('interview_registration_campaigns', function (Blueprint $table) {
            $table->dropForeign(['user_folder_id']);
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('interview_registration_campaigns');
    }
};
