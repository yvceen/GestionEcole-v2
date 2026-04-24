<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_user_id')->index();
                $table->string('title');
                $table->dateTime('scheduled_at')->index();
                $table->string('parent_phone')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->default('pending')->index();
                $table->timestamps();
            });

            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'parent_user_id')) {
                $table->unsignedBigInteger('parent_user_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('appointments', 'title')) {
                $table->string('title')->after('parent_user_id');
            }

            if (!Schema::hasColumn('appointments', 'scheduled_at')) {
                $table->dateTime('scheduled_at')->nullable()->after('title')->index();
            }

            if (!Schema::hasColumn('appointments', 'parent_phone')) {
                $table->string('parent_phone')->nullable()->after('scheduled_at');
            }

            if (!Schema::hasColumn('appointments', 'message')) {
                $table->text('message')->nullable()->after('parent_phone');
            }

            if (!Schema::hasColumn('appointments', 'status')) {
                $table->string('status')->default('pending')->after('message')->index();
            }

            if (!Schema::hasColumn('appointments', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('appointments', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        if (Schema::hasColumn('appointments', 'parent_id')) {
            DB::statement('UPDATE appointments SET parent_user_id = parent_id WHERE parent_user_id IS NULL AND parent_id IS NOT NULL');
        }

        DB::statement("UPDATE appointments SET status = 'approved' WHERE status IN ('confirmed')");
        DB::statement("UPDATE appointments SET status = 'rejected' WHERE status IN ('cancelled', 'archived')");
        DB::statement("UPDATE appointments SET status = 'pending' WHERE status IS NULL OR status = '' OR status NOT IN ('pending','approved','rejected')");
    }

    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'parent_user_id')) {
                $table->dropColumn('parent_user_id');
            }
        });
    }
};
