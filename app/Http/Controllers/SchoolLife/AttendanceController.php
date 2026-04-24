<?php

namespace App\Http\Controllers\SchoolLife;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\AttendanceReportingService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceReportingService $attendanceReporting,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        return view('school-life.attendance.index', $this->attendanceReporting->buildMonitoringData($schoolId, $request));
    }

    public function manual(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        $operator = auth()->user();
        $classroomId = (int) $request->integer('classroom_id');
        $date = (string) $request->get('date', now()->toDateString());

        $classrooms = Classroom::query()
            ->where('school_id', $schoolId)
            ->with('level:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'level_id']);

        $selectedClassroom = $classrooms->firstWhere('id', $classroomId);
        $students = collect();
        $attendanceByStudentId = [];

        if ($selectedClassroom) {
            $students = Student::query()
                ->where('school_id', $schoolId)
                ->where('classroom_id', $selectedClassroom->id)
                ->active()
                ->orderBy('full_name')
                ->get();

            $attendanceByStudentId = Attendance::query()
                ->where('school_id', $schoolId)
                ->where('classroom_id', $selectedClassroom->id)
                ->whereDate('date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id')
                ->all();
        }

        $sessionHistory = $this->attendanceReporting->teacherSessionHistory(
            $schoolId,
            (int) $operator->id,
            $classrooms->pluck('id')->all()
        );

        return view('school-life.attendance.manual', compact(
            'classrooms',
            'students',
            'classroomId',
            'date',
            'attendanceByStudentId',
            'sessionHistory',
            'selectedClassroom'
        ));
    }

    public function storeManual(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        $operator = auth()->user();
        $data = $request->validate([
            'classroom_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.status' => ['required', 'in:' . implode(',', Attendance::statuses())],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $classroom = Classroom::query()
            ->where('school_id', $schoolId)
            ->where('id', (int) $data['classroom_id'])
            ->first(['id', 'name']);
        abort_unless($classroom, 404);

        $students = Student::query()
            ->where('school_id', $schoolId)
            ->where('classroom_id', $classroom->id)
            ->active()
            ->whereIn('id', array_map('intval', array_keys($data['attendance'])))
            ->get(['id', 'full_name', 'parent_user_id']);

        abort_if($students->isEmpty(), 422, 'Aucun eleve valide pour cet appel.');

        $alerts = collect();

        DB::transaction(function () use ($students, $data, $operator, $schoolId, $classroom, &$alerts): void {
            foreach ($students as $student) {
                $payload = $data['attendance'][$student->id] ?? null;
                if (!$payload) {
                    continue;
                }

                $attendance = Attendance::query()->firstOrNew([
                    'student_id' => $student->id,
                    'date' => $data['date'],
                ]);

                $originalStatus = $attendance->exists ? (string) $attendance->status : null;

                $attendance->fill([
                    'school_id' => $schoolId,
                    'classroom_id' => $classroom->id,
                    'status' => $payload['status'],
                    'note' => trim((string) ($payload['note'] ?? '')) ?: null,
                    'marked_by_user_id' => $operator->id,
                    'recorded_via' => Attendance::RECORDED_VIA_MANUAL,
                ]);
                $attendance->save();

                if (
                    in_array($attendance->status, [Attendance::STATUS_ABSENT, Attendance::STATUS_LATE], true)
                    && $attendance->status !== $originalStatus
                ) {
                    $alerts->push([
                        'attendance' => [
                            'date' => Carbon::parse($attendance->date)->toDateString(),
                            'status' => $attendance->status,
                            'note' => $attendance->note,
                        ],
                        'student' => $student,
                    ]);
                }
            }
        });

        $this->sendAttendanceAlerts($alerts, $classroom);

        return redirect()->route('school-life.attendance.manual', [
            'classroom_id' => $classroom->id,
            'date' => $data['date'],
        ])->with('success', 'Registre enregistre avec succes.');
    }

    public function export(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        abort_unless($schoolId > 0, 403, 'School context missing.');

        $records = $this->attendanceReporting->exportMonitoringRecords($schoolId, $request);
        $schoolName = app()->bound('currentSchool')
            ? (app('currentSchool')?->name ?? 'ecole')
            : (app()->bound('current_school') ? (app('current_school')?->name ?? 'ecole') : 'ecole');
        $filename = 'absences-retards-' . now()->format('Ymd-His') . '.xls';

        return response($this->buildSpreadsheetXml($records, (string) $schoolName), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'public',
        ]);
    }

    private function buildSpreadsheetXml(Collection $records, string $schoolName): string
    {
        $escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $escapedSchoolName = $escape($schoolName);
        $escapedGeneratedAt = $escape(now()->format('d/m/Y H:i'));

        $rows = '';
        foreach ($records as $attendance) {
            $statusLabel = match ((string) $attendance->status) {
                Attendance::STATUS_PRESENT => 'Present',
                Attendance::STATUS_ABSENT => 'Absent',
                Attendance::STATUS_LATE => 'En retard',
                default => ucfirst((string) $attendance->status),
            };

            $cells = [
                $attendance->student?->full_name ?? '',
                $attendance->classroom?->name ?? '',
                optional($attendance->date)->format('d/m/Y') ?? '',
                $statusLabel,
                optional($attendance->check_in_at)->format('H:i') ?? '',
                optional($attendance->check_out_at)->format('H:i') ?? '',
                match ((string) $attendance->recorded_via) {
                    Attendance::RECORDED_VIA_QR => 'QR scan',
                    Attendance::RECORDED_VIA_MANUAL => 'Correction',
                    default => 'Appel enseignant',
                },
                $attendance->note ?? '',
                $attendance->scannedBy?->name ?? $attendance->markedBy?->name ?? '',
            ];

            $rows .= '<Row>';
            foreach ($cells as $cell) {
                $rows .= '<Cell><Data ss:Type="String">' . $escape((string) $cell) . '</Data></Cell>';
            }
            $rows .= '</Row>';
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
    <Worksheet ss:Name="Absences">
        <Table>
            <Row>
                <Cell><Data ss:Type="String">Etablissement</Data></Cell>
                <Cell><Data ss:Type="String">{$escapedSchoolName}</Data></Cell>
            </Row>
            <Row>
                <Cell><Data ss:Type="String">Export genere</Data></Cell>
                <Cell><Data ss:Type="String">{$escapedGeneratedAt}</Data></Cell>
            </Row>
            <Row/>
            <Row>
                <Cell><Data ss:Type="String">Eleve</Data></Cell>
                <Cell><Data ss:Type="String">Classe</Data></Cell>
                <Cell><Data ss:Type="String">Date</Data></Cell>
                <Cell><Data ss:Type="String">Statut</Data></Cell>
                <Cell><Data ss:Type="String">Entree</Data></Cell>
                <Cell><Data ss:Type="String">Sortie</Data></Cell>
                <Cell><Data ss:Type="String">Source</Data></Cell>
                <Cell><Data ss:Type="String">Note</Data></Cell>
                <Cell><Data ss:Type="String">Operateur</Data></Cell>
            </Row>
            {$rows}
        </Table>
    </Worksheet>
</Workbook>
XML;
    }

    private function sendAttendanceAlerts(Collection $alerts, Classroom $classroom): void
    {
        $alerts->each(function (array $item) use ($classroom): void {
            $student = $item['student'];
            $attendance = $item['attendance'];

            if (!$student->parent_user_id) {
                return;
            }

            $statusLabel = $attendance['status'] === Attendance::STATUS_LATE ? 'en retard' : 'absent';

            $this->notifications->notifyUsers(
                [(int) $student->parent_user_id],
                'attendance',
                'Nouvelle alerte de presence',
                sprintf(
                    '%s a ete marque %s le %s%s.',
                    $student->full_name,
                    $statusLabel,
                    Carbon::parse($attendance['date'])->format('d/m/Y'),
                    $attendance['note'] ? ' (' . $attendance['note'] . ')' : ''
                ),
                [
                    'student_id' => $student->id,
                    'classroom_id' => $classroom->id,
                    'date' => $attendance['date'],
                    'status' => $attendance['status'],
                    'route' => route('parent.attendance.index', absolute: false),
                ]
            );
        });
    }
}
