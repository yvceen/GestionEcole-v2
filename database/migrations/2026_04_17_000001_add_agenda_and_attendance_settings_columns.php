<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                if (!Schema::hasColumn('news', 'source_type')) {
                    $table->string('source_type', 40)->nullable()->after('classroom_id')->index();
                }
                if (!Schema::hasColumn('news', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
                }
            });
        }

        if (Schema::hasTable('timetable_settings')) {
            Schema::table('timetable_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('timetable_settings', 'late_grace_minutes')) {
                    $table->unsignedSmallInteger('late_grace_minutes')->default(15)->after('day_start_time');
                }
                if (!Schema::hasColumn('timetable_settings', 'auto_absent_cutoff_time')) {
                    $table->time('auto_absent_cutoff_time')->nullable()->after('day_end_time');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                $drops = [];
                if (Schema::hasColumn('news', 'source_id')) {
                    $drops[] = 'source_id';
                }
                if (Schema::hasColumn('news', 'source_type')) {
                    $drops[] = 'source_type';
                }
                if ($drops !== []) {
                    $table->dropColumn($drops);
                }
            });
        }

        if (Schema::hasTable('timetable_settings')) {
            Schema::table('timetable_settings', function (Blueprint $table) {
                $drops = [];
                if (Schema::hasColumn('timetable_settings', 'auto_absent_cutoff_time')) {
                    $drops[] = 'auto_absent_cutoff_time';
                }
                if (Schema::hasColumn('timetable_settings', 'late_grace_minutes')) {
                    $drops[] = 'late_grace_minutes';
                }
                if ($drops !== []) {
                    $table->dropColumn($drops);
                }
            });
        }
    }
};
