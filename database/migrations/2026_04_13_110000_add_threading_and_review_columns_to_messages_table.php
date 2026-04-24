<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'approval_required')) {
                $table->boolean('approval_required')->default(false)->after('body');
            }

            if (!Schema::hasColumn('messages', 'thread_id')) {
                $table->unsignedBigInteger('thread_id')->nullable()->after('approval_required')->index();
            }

            if (!Schema::hasColumn('messages', 'reply_to_id')) {
                $table->unsignedBigInteger('reply_to_id')->nullable()->after('thread_id')->index();
            }

            if (!Schema::hasColumn('messages', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->after('approved_at')->index();
            }

            if (!Schema::hasColumn('messages', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'thread_id')) {
                $table->dropIndex(['thread_id']);
                $table->dropColumn('thread_id');
            }

            if (Schema::hasColumn('messages', 'reply_to_id')) {
                $table->dropIndex(['reply_to_id']);
                $table->dropColumn('reply_to_id');
            }

            if (Schema::hasColumn('messages', 'rejected_by')) {
                $table->dropIndex(['rejected_by']);
                $table->dropColumn('rejected_by');
            }

            foreach (['approval_required', 'rejected_at'] as $column) {
                if (Schema::hasColumn('messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
