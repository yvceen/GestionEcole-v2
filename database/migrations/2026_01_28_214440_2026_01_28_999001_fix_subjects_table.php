<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('name')->nullable();
                $table->timestamps();
            });
            return;
        }

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('subjects', 'name')) {
                $table->string('name')->nullable()->after('school_id');
            }
            if (!Schema::hasColumn('subjects', 'created_at')) {
                $table->timestamps();
            }
        });

        // ✅ محاولة تعمير name من columns قديمة إذا كانوا كاينين
        $cols = collect(DB::select("SHOW COLUMNS FROM `subjects`"))
            ->pluck('Field')
            ->map(fn($x) => (string)$x)
            ->all();

        $candidates = ['title', 'label', 'libelle', 'matiere', 'subject', 'nom'];
        $from = null;
        foreach ($candidates as $c) {
            if (in_array($c, $cols, true)) { $from = $c; break; }
        }

        if ($from) {
            DB::statement("UPDATE `subjects` SET `name` = `$from` WHERE `name` IS NULL OR `name` = ''");
        }
    }

    public function down(): void
    {
        // ما نحيد حتى حاجة باش ما نخسروش الداتا
    }
};
