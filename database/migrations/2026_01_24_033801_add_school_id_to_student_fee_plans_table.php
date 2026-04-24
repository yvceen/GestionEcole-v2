<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table) {

            // 1️⃣ زيد school_id إلا ما كانش
            if (!Schema::hasColumn('student_fee_plans', 'school_id')) {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->cascadeOnDelete();
            }

            // 2️⃣ حيد foreign key ديال student_id (إجباري قبل unique)
            $table->dropForeign(['student_id']);

            // 3️⃣ دابا حيد unique القديم
            $table->dropUnique('student_fee_plans_student_id_unique');

            // 4️⃣ رجّع foreign key ديال student_id
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            // 5️⃣ unique جديد per school
            $table->unique(['school_id', 'student_id'], 'sfp_school_student_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_fee_plans', function (Blueprint $table) {

            // 1️⃣ حيد unique الجديد
            $table->dropUnique('sfp_school_student_unique');

            // 2️⃣ حيد foreign key ديال student_id
            $table->dropForeign(['student_id']);

            // 3️⃣ رجّع unique القديم
            $table->unique('student_id', 'student_fee_plans_student_id_unique');

            // 4️⃣ رجّع foreign key ديال student_id
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->cascadeOnDelete();

            // 5️⃣ حيد school_id
            if (Schema::hasColumn('student_fee_plans', 'school_id')) {
                $table->dropConstrainedForeignId('school_id');
            }
        });
    }
};
