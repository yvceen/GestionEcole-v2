<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StaffAttendanceLog;
use App\Models\StaffAttendanceMapping;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ZkTimeAttendanceController extends Controller
{
    public function store(Request $request)
    {
        $this->authorizeConnector($request);

        $data = $request->validate([
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'source_file' => ['nullable', 'string', 'max:255'],
            'records' => ['required', 'array', 'max:1000'],
            'records.*.employee_code' => ['required', 'string', 'max:80'],
            'records.*.first_name' => ['nullable', 'string', 'max:255'],
            'records.*.last_name' => ['nullable', 'string', 'max:255'],
            'records.*.employee_name' => ['nullable', 'string', 'max:255'],
            'records.*.department_code' => ['nullable', 'string', 'max:80'],
            'records.*.department_name' => ['nullable', 'string', 'max:255'],
            'records.*.date' => ['required', 'date_format:Y-m-d'],
            'records.*.time' => ['required', 'date_format:H:i:s'],
            'records.*.verify_type' => ['nullable', 'string', 'max:80'],
            'records.*.punch_state' => ['nullable', 'string', 'max:80'],
            'records.*.work_code' => ['nullable', 'string', 'max:80'],
            'records.*.card_number' => ['nullable', 'string', 'max:120'],
            'records.*.area_name' => ['nullable', 'string', 'max:255'],
            'records.*.terminal_alias' => ['nullable', 'string', 'max:255'],
            'records.*.terminal_sn' => ['nullable', 'string', 'max:120'],
            'records.*.raw_line' => ['nullable', 'string'],
        ]);

        $school = School::query()->findOrFail((int) $data['school_id']);
        app()->instance('current_school_id', (int) $school->id);
        app()->instance('currentSchool', $school);

        $imported = 0;
        $updated = 0;
        $mappingsCreated = 0;
        $now = now();

        DB::transaction(function () use ($data, $now, &$imported, &$updated, &$mappingsCreated): void {
            foreach ($data['records'] as $record) {
                $punchedAt = Carbon::createFromFormat('Y-m-d H:i:s', $record['date'] . ' ' . $record['time']);
                $employeeName = trim((string) ($record['employee_name'] ?? trim(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? ''))));

                $mapping = StaffAttendanceMapping::query()->firstOrCreate(
                    [
                        'school_id' => (int) $data['school_id'],
                        'employee_code' => (string) $record['employee_code'],
                    ],
                    [
                        'employee_name' => $employeeName !== '' ? $employeeName : null,
                        'department_code' => $record['department_code'] ?? null,
                        'department_name' => $record['department_name'] ?? null,
                        'is_active' => true,
                    ]
                );

                if ($mapping->wasRecentlyCreated) {
                    $mappingsCreated++;
                }

                $mapping->forceFill([
                    'employee_name' => $employeeName !== '' ? $employeeName : $mapping->employee_name,
                    'department_code' => $record['department_code'] ?? $mapping->department_code,
                    'department_name' => $record['department_name'] ?? $mapping->department_name,
                    'last_seen_at' => $punchedAt,
                ])->save();

                $payload = [
                    'user_id' => $mapping->user_id,
                    'first_name' => $record['first_name'] ?? null,
                    'last_name' => $record['last_name'] ?? null,
                    'employee_name' => $employeeName !== '' ? $employeeName : null,
                    'department_code' => $record['department_code'] ?? null,
                    'department_name' => $record['department_name'] ?? null,
                    'punch_date' => $record['date'],
                    'punch_time' => $record['time'],
                    'verify_type' => $record['verify_type'] ?? null,
                    'punch_state' => $record['punch_state'] ?? null,
                    'work_code' => $record['work_code'] ?? null,
                    'card_number' => $record['card_number'] ?? null,
                    'area_name' => $record['area_name'] ?? null,
                    'terminal_alias' => $record['terminal_alias'] ?? null,
                    'source_file' => $data['source_file'] ?? null,
                    'raw_line' => $record['raw_line'] ?? null,
                    'imported_at' => $now,
                ];

                $log = StaffAttendanceLog::query()->updateOrCreate(
                    [
                        'school_id' => (int) $data['school_id'],
                        'employee_code' => (string) $record['employee_code'],
                        'punched_at' => $punchedAt,
                        'terminal_sn' => $record['terminal_sn'] ?? null,
                    ],
                    $payload
                );

                $log->wasRecentlyCreated ? $imported++ : $updated++;
            }
        });

        return response()->json([
            'ok' => true,
            'received' => count($data['records']),
            'imported' => $imported,
            'duplicates_updated' => $updated,
            'mappings_created' => $mappingsCreated,
        ]);
    }

    private function authorizeConnector(Request $request): void
    {
        $expected = (string) config('services.zktime.token', '');
        $provided = (string) ($request->bearerToken() ?: $request->header('X-ZKTIME-TOKEN', ''));

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            throw ValidationException::withMessages([
                'token' => 'Jeton ZKBioTime invalide.',
            ]);
        }
    }
}
