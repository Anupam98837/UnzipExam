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
        Schema::table('path_game_results', function (Blueprint $table) {
            // tinyint(1) style flag: 0 = not published, 1 = published
            $table->unsignedTinyInteger('publish_to_student')
                  ->default(0)
                  ->after('score'); // ✅ keep same placement style
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('path_game_results', function (Blueprint $table) {
            $table->dropColumn('publish_to_student');
        });
    }
};
