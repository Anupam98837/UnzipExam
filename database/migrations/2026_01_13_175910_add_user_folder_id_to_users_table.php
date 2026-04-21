<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // add nullable FK column
            $table->unsignedBigInteger('user_folder_id')->nullable()->after('uuid')->index();
 
            // FK constraint
            $table->foreign('user_folder_id')
                ->references('id')->on('user_folders')
                ->nullOnDelete(); // if folder deleted, set users.user_folder_id = null
        });
    }
 
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['user_folder_id']);
            $table->dropColumn('user_folder_id');
        });
    }
};