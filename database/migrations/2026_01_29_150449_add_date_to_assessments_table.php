<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('assessments', 'date')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->date('date')->nullable()->index();
            });
        }

        // ✅ Backfill: إذا ما عندكش data، خليه اليوم
        DB::table('assessments')->whereNull('date')->update(['date' => now()->toDateString()]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'date')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropIndex(['date']);
                $table->dropColumn('date');
            });
        }
    }
};