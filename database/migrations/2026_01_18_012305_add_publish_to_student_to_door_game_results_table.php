<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('door_game_results', function (Blueprint $table) {
            // ✅ publish_to_student : 0 = not published, 1 = published
            $table->tinyInteger('publish_to_student')
                  ->default(0)
                  ->index()
                  ->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('door_game_results', function (Blueprint $table) {
            $table->dropColumn('publish_to_student');
        });
    }
};
