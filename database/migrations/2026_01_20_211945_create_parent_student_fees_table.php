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
    Schema::create('parent_student_fees', function (Blueprint $table) {
        $table->id();

        $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();

        // شهري
        $table->decimal('tuition_monthly', 10, 2)->default(0);
        $table->decimal('transport_monthly', 10, 2)->default(0);
        $table->decimal('canteen_monthly', 10, 2)->default(0);

        // سنوي
        $table->decimal('insurance_yearly', 10, 2)->default(0);

        // متى كيبدأ الأداء (غالباً 9)
        $table->unsignedTinyInteger('starts_month')->default(9);

        $table->text('notes')->nullable();
        $table->timestamps();

        // parent + student خاصهم يكونو unique
        $table->unique(['parent_user_id', 'student_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student_fees');
    }
};
