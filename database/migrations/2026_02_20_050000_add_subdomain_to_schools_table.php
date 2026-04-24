<?php

use App\Services\SchoolDomainService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('schools')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'subdomain')) {
                $table->string('subdomain', 120)->nullable()->after('slug');
            }
        });

        $domainService = app(SchoolDomainService::class);
        $schools = DB::table('schools')
            ->select('id', 'name', 'slug', 'subdomain')
            ->orderBy('id')
            ->get();

        foreach ($schools as $school) {
            $source = (string) ($school->subdomain ?: $school->slug ?: $school->name ?: ('school-' . $school->id));
            $subdomain = $domainService->generateUniqueSubdomain($source, (int) $school->id);

            if ((string) $school->subdomain !== $subdomain) {
                DB::table('schools')
                    ->where('id', $school->id)
                    ->update([
                        'subdomain' => $subdomain,
                        'updated_at' => now(),
                    ]);
            }
        }

        if (!$this->indexExists('schools', 'schools_subdomain_unique')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->unique('subdomain', 'schools_subdomain_unique');
            });
        }

        if ($this->usesMysql()) {
            DB::statement('ALTER TABLE `schools` MODIFY `subdomain` VARCHAR(120) NOT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('schools') || !Schema::hasColumn('schools', 'subdomain')) {
            return;
        }

        if ($this->indexExists('schools', 'schools_subdomain_unique')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropUnique('schools_subdomain_unique');
            });
        }

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('subdomain');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($index) => ($index->name ?? null) === $indexName);
        }

        $result = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);

        return !empty($result);
    }

    private function usesMysql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
