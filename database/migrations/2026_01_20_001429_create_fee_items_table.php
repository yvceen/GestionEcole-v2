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
        Schema::create('fee_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Scolarité, Transport, Cantine, Assurance...
            $table->enum('billing_type', ['monthly','yearly','one_time']);
            $table->decimal('default_amount', 10, 2)->nullable();
            $table->unsignedTinyInteger('due_month')->nullable(); // e.g. 9 for September (annual/assurance)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_items');
    }
};
