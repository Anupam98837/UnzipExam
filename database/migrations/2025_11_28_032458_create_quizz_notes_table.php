<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quizz_notes')) {
            Schema::create('quizz_notes', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Link to quizzes
                $table->unsignedBigInteger('quiz_id')->index();

                // The note itself
                $table->text('note');

                // Who wrote it
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->string('created_by_role', 50)->nullable();

                $table->timestamps();

                // Optional FK if you want:
                // $table->foreign('quiz_id')
                //       ->references('id')->on('quizz')
                //       ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quizz_notes');
    }
};
