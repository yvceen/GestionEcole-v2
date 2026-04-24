<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'recipient_user_id')) {
                $table->unsignedBigInteger('recipient_user_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('notifications', 'recipient_role')) {
                $table->string('recipient_role', 30)->nullable()->after('recipient_user_id')->index();
            }
        });

        if (Schema::hasColumn('notifications', 'user_id') && Schema::hasColumn('notifications', 'recipient_user_id')) {
            DB::table('notifications')
                ->whereNull('recipient_user_id')
                ->update(['recipient_user_id' => DB::raw('user_id')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'recipient_role')) {
                $table->dropColumn('recipient_role');
            }
            if (Schema::hasColumn('notifications', 'recipient_user_id')) {
                $table->dropColumn('recipient_user_id');
            }
        });
    }
};
