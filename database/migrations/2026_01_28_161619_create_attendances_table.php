<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();

            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('classroom_id')->index();

            $table->date('date');
            $table->enum('status', ['present','absent','late'])->default('present');

            $table->text('note')->nullable();
            $table->unsignedBigInteger('marked_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['student_id','date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
