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

        Schema::create('user_folders', function (Blueprint $table) {

            $table->bigIncrements('id');
 
            // External identifier (recommended)

            $table->uuid('uuid')->unique();
 
            // Core fields

            $table->string('title', 180);

            $table->text('description')->nullable();

            $table->text('reason')->nullable();
 
            // As requested

            $table->string('status', 20)->default('active'); // active/inactive

            $table->json('metadata')->nullable(); // extra config (future settings)
 
            // Audit

            $table->unsignedBigInteger('created_by')->nullable(); // FK -> users.id

            $table->string('created_at_ip', 45)->nullable();

            $table->string('updated_at_ip', 45)->nullable();
 
            // Timestamps + soft delete

            $table->timestamps();

            $table->softDeletes()->index();
 
            // Indexes

            $table->index('status');

            $table->index('created_by');
 
            // FK

            $table->foreign('created_by')

                ->references('id')->on('users')

                ->nullOnDelete();

        });

    }
 
    /**

     * Reverse the migrations.

     */

    public function down(): void

    {

        Schema::table('user_folders', function (Blueprint $table) {

            $table->dropForeign(['created_by']);

        });
 
        Schema::dropIfExists('user_folders');

    }

};

 