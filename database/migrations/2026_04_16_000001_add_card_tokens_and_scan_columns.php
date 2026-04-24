<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'card_token')) {
                $table->string('card_token', 32)->nullable()->unique()->after('remember_token');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'card_token')) {
                $table->string('card_token', 32)->nullable()->unique()->after('user_id');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'check_in_at')) {
                $table->dateTime('check_in_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('attendances', 'check_out_at')) {
                $table->dateTime('check_out_at')->nullable()->after('check_in_at');
            }

            if (!Schema::hasColumn('attendances', 'scanned_by_user_id')) {
                $table->unsignedBigInteger('scanned_by_user_id')->nullable()->after('marked_by_user_id')->index();
            }

            if (!Schema::hasColumn('attendances', 'recorded_via')) {
                $table->string('recorded_via', 32)->default('teacher_register')->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            foreach (['check_in_at', 'check_out_at', 'scanned_by_user_id', 'recorded_via'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'card_token')) {
                $table->dropUnique('students_card_token_unique');
                $table->dropColumn('card_token');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'card_token')) {
                $table->dropUnique('users_card_token_unique');
                $table->dropColumn('card_token');
            }
        });
    }
};
