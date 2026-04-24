<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'status')) {
                $table->string('status')->default('pending')->after('published_at');
            }
            if (!Schema::hasColumn('courses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('courses', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            }
        });

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
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('courses', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('courses', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('homeworks', function (Blueprint $table) {
            if (Schema::hasColumn('homeworks', 'approved_by')) {
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('homeworks', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('homeworks', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
