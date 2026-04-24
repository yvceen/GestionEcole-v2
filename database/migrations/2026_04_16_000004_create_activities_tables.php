<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 32)->index();
            $table->dateTime('start_date')->index();
            $table->dateTime('end_date')->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->string('color', 24)->nullable();
            $table->timestamps();
        });

        Schema::create('activity_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('activity_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('confirmation_status', 24)->default('pending')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('attendance_status', 24)->nullable()->index();
            $table->timestamp('attended_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'student_id']);
        });

        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('activity_id')->index();
            $table->unsignedBigInteger('created_by_user_id')->index();
            $table->text('report_text');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
        Schema::dropIfExists('activity_participants');
        Schema::dropIfExists('activities');
    }
};
