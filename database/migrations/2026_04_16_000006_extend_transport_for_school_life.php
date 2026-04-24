<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicles', 'name')) {
                    $table->string('name')->nullable()->after('school_id');
                }
                if (!Schema::hasColumn('vehicles', 'assistant_name')) {
                    $table->string('assistant_name')->nullable()->after('driver_id');
                }
            });
        }

        if (Schema::hasTable('route_stops')) {
            Schema::table('route_stops', function (Blueprint $table) {
                if (!Schema::hasColumn('route_stops', 'scheduled_time')) {
                    $table->time('scheduled_time')->nullable()->after('stop_order');
                }
            });
        }

        Schema::create('transport_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('transport_assignment_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('route_id')->index();
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            $table->unsignedBigInteger('route_stop_id')->nullable()->index();
            $table->string('status', 16)->index();
            $table->unsignedBigInteger('recorded_by_user_id')->index();
            $table->timestamp('logged_at')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_logs');

        if (Schema::hasTable('route_stops') && Schema::hasColumn('route_stops', 'scheduled_time')) {
            Schema::table('route_stops', function (Blueprint $table) {
                $table->dropColumn('scheduled_time');
            });
        }

        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $drops = [];
                if (Schema::hasColumn('vehicles', 'assistant_name')) {
                    $drops[] = 'assistant_name';
                }
                if (Schema::hasColumn('vehicles', 'name')) {
                    $drops[] = 'name';
                }
                if (!empty($drops)) {
                    $table->dropColumn($drops);
                }
            });
        }
    }
};
