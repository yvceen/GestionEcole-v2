<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('classroom_id')->index();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('subject_id')->index();

            $table->string('title'); // Contrôle 1, Examen...
            $table->date('date');
            $table->unsignedTinyInteger('max_score')->default(20);

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
