<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_fee_plans')) {
            return;
        }

        // Drop legacy fee_item_id if it still exists.
        if (Schema::hasColumn('student_fee_plans', 'fee_item_id')) {
            $fk = DB::selectOne(
                "SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'student_fee_plans'
                   AND COLUMN_NAME = 'fee_item_id'
                   AND CONSTRAINT_NAME != 'PRIMARY'
                 LIMIT 1"
            );

            if ($fk?->CONSTRAINT_NAME) {
                DB::statement("ALTER TABLE `student_fee_plans` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            Schema::table('student_fee_plans', function (Blueprint $table) {
                $table->dropColumn('fee_item_id');
            });
        }

        Schema::table('student_fee_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_plans', 'tuition_monthly')) {
                $table->decimal('tuition_monthly', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('student_fee_plans', 'transport_monthly')) {
                $table->decimal('transport_monthly', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('student_fee_plans', 'canteen_monthly')) {
                $table->decimal('canteen_monthly', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('student_fee_plans', 'insurance_yearly')) {
                $table->decimal('insurance_yearly', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('student_fee_plans', 'starts_month')) {
                $table->unsignedTinyInteger('starts_month')->default(9);
            }
            if (!Schema::hasColumn('student_fee_plans', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: safe guard migration.
    }
};
