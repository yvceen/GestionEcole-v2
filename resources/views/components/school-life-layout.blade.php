@props([
    'title' => 'Vie scolaire',
    'subtitle' => 'Suivi operationnel des eleves, presences, contacts parents et demandes de sortie.',
])

@php
    use Illuminate\Support\Facades\Route;

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'school-life.dashboard', 'icon' => 'home'],
        ['label' => 'Eleves', 'route' => 'school-life.students.index', 'icon' => 'users', 'active_routes' => ['school-life.students.*']],
        ['label' => 'Presences', 'route' => 'school-life.attendance.index', 'icon' => 'shield'],
        ['label' => 'Devoirs', 'route' => 'school-life.homeworks.index', 'icon' => 'clipboard', 'active_routes' => ['school-life.homeworks.*']],
        ['label' => 'Activites', 'route' => 'school-life.activities.index', 'icon' => 'calendar', 'active_routes' => ['school-life.activities.*']],
        ['label' => 'Transport', 'route' => 'transport.ops.index', 'icon' => 'users', 'active_routes' => ['transport.ops.*']],
        ['label' => 'Scan QR', 'route' => 'attendance.scan.page', 'icon' => 'shield', 'active_routes' => ['attendance.scan.page']],
        ['label' => 'Agenda', 'route' => 'school-life.events.index', 'icon' => 'calendar'],
        ['label' => 'Cartes', 'route' => 'school-life.cards.index', 'icon' => 'users'],
        ['label' => 'Notes', 'route' => 'school-life.grades.index', 'icon' => 'chart'],
        ['label' => 'Documents', 'route' => 'school-life.documents.registration-requirements.index', 'icon' => 'clipboard'],
        ['label' => 'Recuperations', 'route' => 'school-life.pickup-requests.index', 'icon' => 'calendar'],
    ];

    if (Route::has('school-life.notifications.index')) {
        $nav[] = ['label' => 'Notifications', 'route' => 'school-life.notifications.index', 'icon' => 'bell'];
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
    <title>{{ $title }} | {{ $currentSchool?->name ?? 'MyEdu' }}</title>
    <x-school-favicons />
    @stack('head')
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="app-shell-body min-h-screen overflow-x-hidden m-0 p-0">
    <x-app-shell :links="$nav" navigation-title="Vie scolaire">
        <div x-data="portalShell()" data-portal-shell class="ui-scope portal-space space-y-6">
            <div x-cloak x-show="navigating" x-transition.opacity class="portal-loading-overlay" aria-live="polite" aria-busy="true">
                <div class="portal-loading-card">
                    <span class="portal-spinner" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-slate-900" x-text="loadingLabel"></p>
                </div>
            </div>

            <div x-cloak x-show="navigating" class="portal-loading-bar"></div>

            <x-portal-header
                eyebrow="Vie scolaire"
                :title="$title"
                :subtitle="$subtitle"
                :badges="[
                    auth()->user()?->name ?? 'Responsable scolaire',
                    $currentSchool?->name ?? 'Ecole active',
                    $activeNav['label'] ?? 'Suivi',
                ]"
                summary-title="Module actif"
                :summary-value="$activeNav['label'] ?? 'Vie scolaire'"
                summary-copy="Acces operationnel limite: suivi des eleves, presences, contacts et demandes de recuperation."
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
