<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('teacher_id')->index();
            $table->unsignedBigInteger('subject_id')->index();

            $table->unsignedBigInteger('assigned_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['teacher_id','subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subject');
    }
};
