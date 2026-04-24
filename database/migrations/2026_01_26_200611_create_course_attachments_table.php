<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_attachments', function (Blueprint $table) {
            $table->id();

            // ✅ multi-school
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('course_id')->index();

            $table->string('original_name');
            $table->string('path');          // storage path (public disk)
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')->on('courses')
                ->onDelete('cascade');

            // ✅ مفيد فالفيلترة
            $table->index(['school_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_attachments');
    }
};