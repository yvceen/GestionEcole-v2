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
        Schema::create('student_fee_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
    
            $table->decimal('tuition_monthly', 10, 2)->default(0);
            $table->decimal('transport_monthly', 10, 2)->default(0);
            $table->decimal('canteen_monthly', 10, 2)->default(0);
            $table->decimal('insurance_yearly', 10, 2)->default(0);
    
            $table->unsignedTinyInteger('starts_month')->default(9);
            $table->text('notes')->nullable();
    
            $table->timestamps();
    
            $table->unique('student_id');
        });
    }
    

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_plans');
    }
    

};
