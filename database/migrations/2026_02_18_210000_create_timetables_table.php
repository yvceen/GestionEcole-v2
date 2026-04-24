<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('day'); // 1=lundi ... 6=samedi
            $table->time('start_time');
            $table->time('end_time');
            $table->string('subject');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room')->nullable();
            $table->timestamps();

            $table->index(['classroom_id', 'day', 'start_time'], 'timetables_class_day_start_idx');
            $table->index(['school_id', 'classroom_id', 'day'], 'timetables_school_class_day_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};

