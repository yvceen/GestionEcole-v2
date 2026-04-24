<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('school_lives') || Schema::hasColumn('school_lives', 'school_id')) {
            return;
        }

        Schema::table('school_lives', function (Blueprint $table): void {
            $table->foreignId('school_id')
                ->nullable()
                ->after('id')
                ->constrained('schools')
                ->nullOnDelete();
        });

        $schoolCount = (int) DB::table('schools')->count();
        $rowCount = (int) DB::table('school_lives')->count();

        if ($schoolCount === 1 && $rowCount > 0) {
            $schoolId = (int) DB::table('schools')->value('id');
            DB::table('school_lives')
                ->whereNull('school_id')
                ->update(['school_id' => $schoolId]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('school_lives') || !Schema::hasColumn('school_lives', 'school_id')) {
            return;
        }

        Schema::table('school_lives', function (Blueprint $table): void {
            try {
                $table->dropForeign(['school_id']);
            } catch (\Throwable $e) {
            }

            $table->dropColumn('school_id');
        });
    }
};
