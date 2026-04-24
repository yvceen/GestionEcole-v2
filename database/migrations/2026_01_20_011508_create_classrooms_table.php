<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('classrooms', function (Blueprint $table) {
        $table->id();
        $table->foreignId('level_id')->constrained()->cascadeOnDelete();

        $table->string('section')->nullable(); // A, B, C, D...
        $table->string('name');                // "CP A", "CE1 B", "1AC"...

        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['level_id', 'section']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
