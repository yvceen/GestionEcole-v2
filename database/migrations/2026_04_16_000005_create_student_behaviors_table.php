<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_behaviors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->string('type', 24)->index();
            $table->text('description');
            $table->unsignedBigInteger('created_by_user_id')->index();
            $table->date('date')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_behaviors');
    }
};
