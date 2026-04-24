<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // ✅ receipt: multiple payments can belong to one receipt
            $table->foreignId('receipt_id')
                ->nullable()
                ->constrained('receipts')
                ->nullOnDelete();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 10, 2);

            $table->enum('method', ['cash','transfer','card','check'])->default('cash');

            // ✅ always store as YYYY-MM-01
            $table->date('period_month');

            // when received
            $table->timestamp('paid_at')->useCurrent();

            $table->foreignId('received_by_admin_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('note')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('paid_at');
            $table->index('period_month');
            $table->index('receipt_id');

            // ✅ prevent duplicate month per student
            $table->unique(['student_id', 'period_month'], 'uniq_student_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
