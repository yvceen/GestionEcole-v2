<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
            $table->string('title');
            $table->string('type', 24)->default('event')->index();
            $table->date('starts_on')->index();
            $table->date('ends_on')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendar_events');
    }
};
