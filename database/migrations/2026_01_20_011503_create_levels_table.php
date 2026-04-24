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
    Schema::create('levels', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique(); // MS, GS, CP, CE1, 1AC...
        $table->string('name');           // MS, GS, CP, CE1...
        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
