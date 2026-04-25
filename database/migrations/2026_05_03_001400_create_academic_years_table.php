<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_years')) {
            return;
        }

        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 80);
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_current')->default(false)->index();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
