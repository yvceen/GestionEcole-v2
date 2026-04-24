<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('classroom_teacher', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('classroom_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id','teacher_id']);
            $table->index(['school_id','teacher_id']);
            $table->index(['school_id','classroom_id']);

            // FK (اختياري إلا كانت عندك constraints)
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('classroom_id')->references('id')->on('classrooms')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_teacher');
    }
};
