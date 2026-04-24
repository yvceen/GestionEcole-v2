<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {

            foreach ([
                'school_id' => 'unsignedBigInteger',
                'classroom_id' => 'unsignedBigInteger',
                'teacher_id' => 'unsignedBigInteger',
                'subject_id' => 'unsignedBigInteger',
                'assessment_id' => 'unsignedBigInteger',
                'student_id' => 'unsignedBigInteger',
            ] as $col => $type) {
                if (!Schema::hasColumn('grades', $col)) {
                    $table->{$type}($col)->nullable()->index();
                }
            }

            if (!Schema::hasColumn('grades', 'score')) {
                $table->decimal('score', 6, 2)->nullable();
            }

            if (!Schema::hasColumn('grades', 'max_score')) {
                $table->unsignedTinyInteger('max_score')->default(20);
            }
        });
    }

    public function down(): void {}
};
