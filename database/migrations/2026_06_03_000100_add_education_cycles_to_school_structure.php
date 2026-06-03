<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name', 80);
            $table->string('color', 20)->default('sky');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->foreignId('education_cycle_id')->nullable()->after('school_id')
                ->constrained('education_cycles')->nullOnDelete();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->unsignedSmallInteger('capacity')->nullable()->after('name');
        });

        $presets = [
            ['code' => 'MAT', 'name' => 'Maternelle', 'color' => 'rose', 'sort_order' => 10],
            ['code' => 'PRI', 'name' => 'Primaire', 'color' => 'sky', 'sort_order' => 20],
            ['code' => 'COL', 'name' => 'Collège', 'color' => 'amber', 'sort_order' => 30],
            ['code' => 'LYC', 'name' => 'Lycée', 'color' => 'violet', 'sort_order' => 40],
        ];

        if (Schema::hasTable('schools')) {
            foreach (DB::table('schools')->pluck('id') as $schoolId) {
                foreach ($presets as $preset) {
                    DB::table('education_cycles')->insert([
                        'school_id' => $schoolId,
                        ...$preset,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $cycles = DB::table('education_cycles')
                    ->where('school_id', $schoolId)
                    ->pluck('id', 'code');

                DB::table('levels')
                    ->where('school_id', $schoolId)
                    ->whereNull('education_cycle_id')
                    ->orderBy('id')
                    ->get(['id', 'code', 'name'])
                    ->each(function ($level) use ($cycles): void {
                        $text = strtoupper((string) $level->code . ' ' . (string) $level->name);
                        $cycleCode = str_contains($text, 'PS') || str_contains($text, 'MS') || str_contains($text, 'GS') || str_contains($text, 'MAT')
                            ? 'MAT'
                            : (str_contains($text, 'AC') || str_contains($text, 'COL')
                                ? 'COL'
                                : (str_contains($text, 'TC') || str_contains($text, 'BAC') || str_contains($text, 'LYC')
                                    ? 'LYC'
                                    : 'PRI'));

                        DB::table('levels')->where('id', $level->id)->update([
                            'education_cycle_id' => $cycles[$cycleCode] ?? null,
                        ]);
                    });
            }
        }
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('education_cycle_id');
        });

        Schema::dropIfExists('education_cycles');
    }
};
