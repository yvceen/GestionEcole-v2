<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            // School (nullable + safe FK)
            $table->unsignedBigInteger('school_id')->nullable();

            $table->string('name');

            // For future soft disabling
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Constraints
            $table->unique(['school_id', 'name']);

            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};