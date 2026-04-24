<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->time('day_start_time')->default('08:00:00');
            $table->time('day_end_time')->default('18:00:00');
            $table->unsignedSmallInteger('slot_minutes')->default(60);
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();
            $table->timestamps();

            $table->unique(['school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_settings');
    }
};

