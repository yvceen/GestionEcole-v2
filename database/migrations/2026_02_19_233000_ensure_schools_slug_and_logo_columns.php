<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('schools')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'slug')) {
                $table->string('slug', 120)->nullable()->after('name');
            }
            if (!Schema::hasColumn('schools', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('slug');
            }
        });

        $schools = DB::table('schools')
            ->select('id', 'name', 'slug')
            ->orderBy('id')
            ->get();

        $used = [];
        foreach ($schools as $school) {
            $base = Str::slug((string) ($school->slug ?: $school->name));
            if ($base === '') {
                $base = 'school-' . (int) $school->id;
            }

            $slug = $base;
            $i = 1;
            while (in_array($slug, $used, true) || DB::table('schools')->where('slug', $slug)->where('id', '<>', $school->id)->exists()) {
                $slug = $base . '-' . $i++;
            }

            $used[] = $slug;

            if ((string) $school->slug !== $slug) {
                DB::table('schools')->where('id', $school->id)->update([
                    'slug' => $slug,
                    'updated_at' => now(),
                ]);
            }
        }

        if (!$this->indexExists('schools', 'schools_slug_unique')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->unique('slug', 'schools_slug_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('schools')) {
            return;
        }

        if ($this->indexExists('schools', 'schools_slug_unique')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropUnique('schools_slug_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);

        return !empty($result);
    }
};
