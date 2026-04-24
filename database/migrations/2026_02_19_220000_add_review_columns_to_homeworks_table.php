<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homeworks')) {
            return;
        }

        Schema::table('homeworks', function (Blueprint $table) {
            if (!Schema::hasColumn('homeworks', 'status')) {
                $table->string('status')->default('pending')->after('due_at');
            }
            if (!Schema::hasColumn('homeworks', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('homeworks', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('homeworks', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('homeworks', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('homeworks')) {
            return;
        }

        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'rejected_by')) {
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('homeworks', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
        });
    }
};
