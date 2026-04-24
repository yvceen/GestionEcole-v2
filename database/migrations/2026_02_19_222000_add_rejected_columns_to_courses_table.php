<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('courses', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'rejected_by')) {
                $table->dropColumn('rejected_by');
            }
            if (Schema::hasColumn('courses', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
        });
    }
};
