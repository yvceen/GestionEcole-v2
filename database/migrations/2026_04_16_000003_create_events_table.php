<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->string('type', 24)->index();
            $table->dateTime('start')->index();
            $table->dateTime('end')->index();
            $table->unsignedBigInteger('classroom_id')->nullable()->index();
            $table->unsignedBigInteger('teacher_id')->nullable()->index();
            $table->string('color', 24)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
