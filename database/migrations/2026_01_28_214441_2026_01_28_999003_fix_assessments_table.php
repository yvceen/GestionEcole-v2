<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // إذا ماكايناش table أصلا كنصايبوها
        if (!Schema::hasTable('assessments')) {
            Schema::create('assessments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->unsignedBigInteger('classroom_id')->nullable()->index();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->string('title')->nullable();
                $table->date('date')->nullable();
                $table->decimal('max_score', 5, 2)->default(20);
                $table->timestamps();
            });
            return;
        }

        // إلا كانت موجودة، كنزيدو غير اللي ناقص
        Schema::table('assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('assessments', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('assessments', 'teacher_id')) {
                // ما نديروش after classroom_id (حيت ممكن ماكايناش)
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
            }
            if (!Schema::hasColumn('assessments', 'classroom_id')) {
                $table->unsignedBigInteger('classroom_id')->nullable()->index();
            }
            if (!Schema::hasColumn('assessments', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->index();
            }
            if (!Schema::hasColumn('assessments', 'title')) {
                $table->string('title')->nullable();
            }
            if (!Schema::hasColumn('assessments', 'date')) {
                $table->date('date')->nullable();
            }
            if (!Schema::hasColumn('assessments', 'max_score')) {
                $table->decimal('max_score', 5, 2)->default(20);
            }
            if (!Schema::hasColumn('assessments', 'created_at')) {
                $table->timestamps();
            }
        });

        // Backfill title من name إذا كاين
        $cols = collect(DB::select("SHOW COLUMNS FROM `assessments`"))
            ->pluck('Field')
            ->map(fn($x) => (string)$x)
            ->all();

        if (in_array('name', $cols, true) && Schema::hasColumn('assessments', 'title')) {
            DB::statement("UPDATE `assessments` SET `title` = `name` WHERE (`title` IS NULL OR `title`='') AND `name` IS NOT NULL");
        }
    }

    public function down(): void
    {
        // ما نحيد حتى حاجة
    }
};
