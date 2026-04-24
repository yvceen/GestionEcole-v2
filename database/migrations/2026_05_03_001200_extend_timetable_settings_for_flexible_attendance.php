<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timetable_settings')) {
            return;
        }

        Schema::table('timetable_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('timetable_settings', 'attendance_sessions')) {
                $table->json('attendance_sessions')->nullable()->after('auto_absent_cutoff_time');
            }
            if (!Schema::hasColumn('timetable_settings', 'allow_manual_time_override')) {
                $table->boolean('allow_manual_time_override')->default(true)->after('attendance_sessions');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('timetable_settings')) {
            return;
        }

        Schema::table('timetable_settings', function (Blueprint $table): void {
            $drops = [];
            if (Schema::hasColumn('timetable_settings', 'attendance_sessions')) {
                $drops[] = 'attendance_sessions';
            }
            if (Schema::hasColumn('timetable_settings', 'allow_manual_time_override')) {
                $drops[] = 'allow_manual_time_override';
            }
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
