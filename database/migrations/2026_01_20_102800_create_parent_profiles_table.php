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
        Schema::create('parent_profiles', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
    
            $table->enum('billing_type', ['monthly','yearly'])
                  ->default('monthly');
    
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->decimal('yearly_fee', 10, 2)->default(0);
    
            $table->decimal('insurance_fee', 10, 2)->default(0);
            $table->decimal('transport_fee', 10, 2)->default(0);
            $table->decimal('canteen_fee', 10, 2)->default(0);
    
            $table->unsignedTinyInteger('starts_month')->default(9); // September
            $table->text('notes')->nullable();
    
            $table->timestamps();
    
            $table->unique('user_id');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_profiles');
    }
    
};
