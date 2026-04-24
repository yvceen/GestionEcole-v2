<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('classroom_id')->index();

            // ✅ باش Teacher/Director يقدرو يديرو where('teacher_id', ...)
            $table->unsignedBigInteger('teacher_id')->nullable()->index();

            $table->string('title');
            $table->longText('description')->nullable();
            $table->timestamp('published_at')->nullable();

            // ✅ خليناه (يمكن كان كيتستعمل فشي بلاصة)
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            // ✅ باش مايبقاش: Unknown column 'file'
            $table->string('file')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'classroom_id']);

            // Foreign keys
            $table->foreign('classroom_id')
                ->references('id')->on('classrooms')
                ->onDelete('cascade');

            $table->foreign('teacher_id')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('created_by_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });

        // ✅ خليتها كما هي (ما نحيدوش شي حاجة كانت موجودة)
        Schema::create('course_files', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('course_id')->index();

            // (اختياري ولكن مزيان) باش نعرفو المدرسة ديال الملف بسهولة
            $table->unsignedBigInteger('school_id')->nullable()->index();

            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);

            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')->on('courses')
                ->onDelete('cascade');

            // ما كنربطوش school_id ب FK باش ما نكسرّوش شي data قديمة
            $table->index(['course_id', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_files');
        Schema::dropIfExists('courses');
    }
};