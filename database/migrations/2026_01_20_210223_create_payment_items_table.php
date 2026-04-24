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
    Schema::create('payment_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();

        // شنو تخلص: tuition/transport/canteen/insurance...
        $table->string('label'); // ex: "Frais mensuels", "Transport", ...
        $table->decimal('amount', 10, 2);

        // optional: الفترة (شهر)
        $table->date('period_month')->nullable();

        $table->timestamps();

        $table->index(['student_id','period_month']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_items');
    }
};
