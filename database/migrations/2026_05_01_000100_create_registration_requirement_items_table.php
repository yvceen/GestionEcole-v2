<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registration_requirement_items')) {
            Schema::create('registration_requirement_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('category', 100);
                $table->string('label', 255);
                $table->text('notes')->nullable();
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_requirement_items');
    }
};
