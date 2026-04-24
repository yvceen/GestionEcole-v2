<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            // رقم الوصل: R-2026-000001
            $table->string('receipt_number')->unique();

            // شكون خلّص (Parent)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // طريقة الأداء
            $table->enum('method', ['cash','transfer','card','check'])->default('cash');

            // مجموع هاد الوصل (باش يبقى ثابت حتى إلا تبدلات fee plans)
            $table->decimal('total_amount', 10, 2)->default(0);

            // وقت إصدار الوصل
            $table->timestamp('issued_at');

            // شكون استقبل (Admin)
            $table->foreignId('received_by_admin_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('note')->nullable();

            $table->timestamps();

            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
