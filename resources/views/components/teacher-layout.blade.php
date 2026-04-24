@props([
    'title' => 'Espace Enseignant',
    'subtitle' => 'Retrouvez vos outils pedagogiques, vos suivis et vos actions rapides dans un espace aligne sur le portail parent.',
])

@php
    use Illuminate\Support\Facades\Route;

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'teacher.dashboard', 'icon' => 'home'],
        ['label' => 'Cours', 'route' => 'teacher.courses.index', 'icon' => 'book'],
        ['label' => 'Devoirs', 'route' => 'teacher.homeworks.index', 'icon' => 'clipboard'],
    ];

    if (Route::has('teacher.grades.index')) {
        $nav[] = ['label' => 'Notes', 'route' => 'teacher.grades.index', 'icon' => 'chart'];
    }
    if (Route::has('teacher.attendance.index')) {
        $nav[] = ['label' => 'Presences', 'route' => 'teacher.attendance.index', 'icon' => 'shield'];
    }
    if (Route::has('attendance.scan.page')) {
        $nav[] = ['label' => 'Scan QR', 'route' => 'attendance.scan.page', 'icon' => 'shield', 'active_routes' => ['attendance.scan.page']];
    }
    if (Route::has('teacher.assessments.index')) {
        $nav[] = ['label' => 'Evaluations', 'route' => 'teacher.assessments.index', 'icon' => 'calendar'];
    }
    if (Route::has('teacher.messages.index')) {
        $nav[] = ['label' => 'Messagerie', 'route' => 'teacher.messages.index', 'icon' => 'message'];
    }
    if (Route::has('teacher.timetable.index')) {
        $nav[] = ['label' => 'Emploi du temps', 'route' => 'teacher.timetable.index', 'icon' => 'calendar'];
    }
    if (Route::has('teacher.events.index')) {
        $nav[] = ['label' => 'Agenda', 'route' => 'teacher.events.index', 'icon' => 'calendar'];
    }
    if (Route::has('teacher.notifications.index')) {
        $nav[] = ['label' => 'Notifications', 'route' => 'teacher.notifications.index', 'icon' => 'bell'];
    }

    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);
    $activeNav = collect($nav)->first(
        fn (array $item) => request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route']))
    );
@endphp

<!doctype html>
<html lang="{{ str_replace('_','-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>MyEdu</title>
    <x-school-favicons />
    @stack('head')
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="app-shell-body min-h-screen overflow-x-hidden m-0 p-0">
    <x-app-shell :links="$nav" navigation-title="Portail enseignant">
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
                eyebrow="Portail enseignant"
                :title="$title"
                :subtitle="$subtitle"
                :badges="[
                    auth()->user()?->name ?? 'Enseignant',
                    $currentSchool?->name ?? 'Ecole active',
                    $activeNav['label'] ?? 'Suivi pedagogique',
                ]"
                summary-title="Organisation"
                :summary-value="$activeNav['label'] ?? 'Espace enseignant'"
                summary-copy="Cours, devoirs, evaluations et suivi des classes restent alignes avec le meme niveau de lisibilite que le portail parent."
                :nav="$nav"
            />

            @if(session('success'))
                <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
            @endif

            {{ $slot }}
        </div>
    </x-app-shell>
    @stack('scripts')
</body>
</html>
