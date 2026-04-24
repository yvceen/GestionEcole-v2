<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        Schema::table('news', function (Blueprint $table): void {
            if (!Schema::hasColumn('news', 'summary')) {
                $table->text('summary')->nullable()->after('title');
            }
            if (!Schema::hasColumn('news', 'body')) {
                $table->longText('body')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('news', 'cover_path')) {
                $table->string('cover_path')->nullable()->after('body');
            }
            if (!Schema::hasColumn('news', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('cover_path')->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('news')) {
            return;
        }

        Schema::table('news', function (Blueprint $table): void {
            $drops = [];
            foreach (['summary', 'body', 'cover_path', 'is_pinned'] as $column) {
                if (Schema::hasColumn('news', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
