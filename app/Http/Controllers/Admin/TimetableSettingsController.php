<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimetableSetting;
use Illuminate\Http\Request;

class TimetableSettingsController extends Controller
{
    public function edit()
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $settings = TimetableSetting::forSchool($schoolId);

        return view('admin.timetable.settings', compact('settings') + [
            'routePrefix' => $this->routePrefix(),
            'layoutComponent' => $this->layoutComponent(),
        ]);
    }

    public function update(Request $request)
    {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : 0;
        if (!$schoolId) {
            abort(403, 'Contexte ecole manquant.');
        }

        $data = $request->validate([
            'day_start_time' => ['required', 'date_format:H:i'],
            'day_end_time' => ['required', 'date_format:H:i'],
            'late_grace_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'auto_absent_cutoff_time' => ['nullable', 'date_format:H:i'],
            'slot_minutes' => ['required', 'integer', 'min:30', 'max:180'],
            'lunch_start' => ['nullable', 'date_format:H:i'],
            'lunch_end' => ['nullable', 'date_format:H:i'],
            'attendance_sessions' => ['nullable', 'array'],
            'attendance_sessions.*.label' => ['nullable', 'string', 'max:80'],
            'attendance_sessions.*.start' => ['nullable', 'date_format:H:i'],
            'attendance_sessions.*.end' => ['nullable', 'date_format:H:i'],
            'allow_manual_time_override' => ['nullable', 'boolean'],
        ], [
            'day_start_time.required' => 'L heure de debut est obligatoire.',
            'day_end_time.required' => 'L heure de fin est obligatoire.',
            'late_grace_minutes.required' => 'Le delai de grace est obligatoire.',
            'slot_minutes.min' => 'La duree d une seance doit etre au minimum de 30 minutes.',
            'slot_minutes.max' => 'La duree d une seance doit etre au maximum de 180 minutes.',
            'lunch_start.date_format' => 'Le format de debut de pause est invalide.',
            'lunch_end.date_format' => 'Le format de fin de pause est invalide.',
        ]);

        $hasLunch = !empty($data['lunch_start']) || !empty($data['lunch_end']);
        if ($hasLunch && (empty($data['lunch_start']) || empty($data['lunch_end']))) {
            return back()->withErrors(['lunch_start' => 'Renseignez debut et fin de pause dejeuner.'])->withInput();
        }

        if ($hasLunch && $data['lunch_start'] >= $data['lunch_end']) {
            return back()->withErrors(['lunch_start' => 'La pause dejeuner est invalide.'])->withInput();
        }

        $attendanceSessions = collect($data['attendance_sessions'] ?? [])
            ->map(function (array $session): ?array {
                $label = trim((string) ($session['label'] ?? ''));
                $start = trim((string) ($session['start'] ?? ''));
                $end = trim((string) ($session['end'] ?? ''));
                if ($start === '' || $end === '') {
                    return null;
                }

                return [
                    'label' => $label !== '' ? $label : 'Session',
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->filter()
            ->values()
            ->all();

        TimetableSetting::updateOrCreate(
            ['school_id' => $schoolId],
            [
                'day_start_time' => $data['day_start_time'],
                'late_grace_minutes' => (int) $data['late_grace_minutes'],
                'day_end_time' => $data['day_end_time'],
                'auto_absent_cutoff_time' => $data['auto_absent_cutoff_time'] ?: null,
                'attendance_sessions' => $attendanceSessions,
                'allow_manual_time_override' => (bool) ($data['allow_manual_time_override'] ?? false),
                'slot_minutes' => (int) $data['slot_minutes'],
                'lunch_start' => $data['lunch_start'] ?: null,
                'lunch_end' => $data['lunch_end'] ?: null,
            ]
        );

        return redirect()->route($this->routePrefix() . '.settings.edit')->with('success', 'Parametres de l emploi du temps mis a jour.');
    }

    protected function routePrefix(): string
    {
        return 'admin.timetable';
    }

    protected function layoutComponent(): string
    {
        return 'admin-layout';
    }
}
