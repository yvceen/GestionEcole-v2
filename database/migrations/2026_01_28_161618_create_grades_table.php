<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('classroom_id')->index();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('subject_id')->index();

            $table->unsignedBigInteger('assessment_id')->nullable()->index();

            $table->decimal('score', 5, 2)->nullable(); // 0..20
            $table->unsignedTinyInteger('max_score')->default(20);

            $table->text('comment')->nullable();

            $table->timestamps();

            // unique per student+assessment
            $table->unique(['student_id','assessment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
