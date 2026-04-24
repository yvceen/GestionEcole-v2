@props([
    'title' => 'Espace eleve',
    'subtitle' => 'Accedez rapidement a votre emploi du temps, vos devoirs, vos notes et vos presences depuis un espace clair.',
])

@php
    $nav = [
        ['label' => 'Accueil', 'route' => 'student.dashboard', 'icon' => 'home', 'active_routes' => ['student.dashboard']],
        ['label' => 'Devoirs', 'route' => 'student.homeworks.index', 'icon' => 'clipboard', 'active_routes' => ['student.homeworks.*', 'student.courses.*']],
        ['label' => 'Notes', 'route' => 'student.grades.index', 'icon' => 'chart', 'active_routes' => ['student.grades.*']],
        ['label' => 'Absences', 'route' => 'student.attendance.index', 'icon' => 'shield', 'active_routes' => ['student.attendance.*']],
        ['label' => 'Agenda', 'route' => 'student.events.index', 'icon' => 'calendar', 'active_routes' => ['student.events.*', 'student.calendar.*']],
        ['label' => 'Activites', 'route' => 'student.activities.index', 'icon' => 'calendar', 'active_routes' => ['student.activities.*']],
        ['label' => 'Transport', 'route' => 'student.transport.index', 'icon' => 'users', 'active_routes' => ['student.transport.*']],
    ];

    if (\Illuminate\Support\Facades\Route::has('student.timetable.index')) {
        array_splice($nav, 1, 0, [[
            'label' => 'Horaire',
            'route' => 'student.timetable.index',
            'icon' => 'calendar',
            'active_routes' => ['student.timetable.*'],
        ]]);
    }

    if (\Illuminate\Support\Facades\Route::has('student.card.show')) {
        $nav[] = ['label' => 'Carte', 'route' => 'student.card.show', 'icon' => 'users', 'active_routes' => ['student.card.*']];
    }

    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);

    $currentStudent = \App\Models\Student::query()
        ->with(['classroom.level'])
        ->where('school_id', app()->bound('current_school_id') ? (int) app('current_school_id') : 0)
        ->where('user_id', auth()->id())
        ->first();

    $studentLabel = $currentStudent?->full_name ?? auth()->user()?->name ?? 'Eleve';
    $classLabel = $currentStudent?->classroom?->name ?? 'Classe non renseignee';
    $levelLabel = $currentStudent?->classroom?->level?->name;

    $headerActions = array_values(array_filter([
        \Illuminate\Support\Facades\Route::has('student.timetable.index')
            ? ['label' => 'Voir mon horaire', 'route' => 'student.timetable.index']
            : null,
        ['label' => 'Voir mes devoirs', 'route' => 'student.homeworks.index'],
        ['label' => 'Voir mes activites', 'route' => 'student.activities.index'],
        ['label' => 'Consulter mes notes', 'route' => 'student.grades.index'],
    ]));
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $title }} | {{ $currentSchool?->name ?? 'MyEdu' }}</title>
    <x-school-favicons />
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="app-shell-body min-h-screen overflow-x-hidden m-0 p-0">
    <x-app-shell :links="$nav" navigation-title="Portail eleve">
        <div x-data="portalShell()" data-portal-shell class="ui-scope portal-space space-y-6">
            <div
                x-cloak
                x-show="navigating"
                x-transition.opacity
                class="portal-loading-overlay"
                aria-live="polite"
                aria-busy="true"
            >
                <div class="portal-loading-card">
                    <span class="portal-spinner" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-slate-900" x-text="loadingLabel"></p>
                </div>
            </div>

            <div x-cloak x-show="navigating" class="portal-loading-bar"></div>

            <x-portal-header
                eyebrow="Portail eleve"
                :title="$title"
                :subtitle="$subtitle"
                :badges="array_values(array_filter([
                    $studentLabel,
                    $classLabel,
                    $levelLabel,
                ]))"
                summary-title="Etablissement"
                :summary-value="$currentSchool?->name ?? 'Ecole active'"
                summary-copy="Les rubriques principales restent visibles, les actions utiles sont plus directes et la lecture est plus nette sur mobile."
                :nav="$nav"
                :actions="$headerActions"
            />

            @if(session('success'))
                <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
            @endif

            {{ $slot }}
        </div>
    </x-app-shell>
</body>
</html>
