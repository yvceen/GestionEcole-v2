@props([
    'title' => 'Vie scolaire',
    'subtitle' => 'Suivi operationnel des Élèves, présences, contacts parents et demandes de sortie.',
])

@php
    use Illuminate\Support\Facades\Route;

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'school-life.dashboard', 'icon' => 'home', 'section' => 'Vue generale'],
        ['label' => 'Structure', 'route' => 'school-life.structure.index', 'icon' => 'calendar', 'active_routes' => ['school-life.structure.*'], 'section' => 'Gestion'],
        ['label' => 'Élèves', 'route' => 'school-life.students.index', 'icon' => 'users', 'active_routes' => ['school-life.students.*'], 'section' => 'Gestion'],
        ['label' => 'Utilisateurs', 'route' => 'school-life.users.index', 'icon' => 'users', 'active_routes' => ['school-life.users.*'], 'section' => 'Gestion'],
        ['label' => 'Cartes', 'route' => 'school-life.cards.index', 'icon' => 'users', 'section' => 'Gestion'],
        ['label' => 'Présences', 'route' => 'school-life.attendance.index', 'icon' => 'shield', 'section' => 'Suivi'],
        ['label' => 'Santé et urgences', 'route' => 'school-life.health.index', 'icon' => 'shield', 'active_routes' => ['school-life.health.*'], 'section' => 'Suivi'],
        ['label' => 'Autorisations numériques', 'route' => 'school-life.digital-authorizations.index', 'icon' => 'clipboard', 'active_routes' => ['school-life.digital-authorizations.*'], 'section' => 'Suivi'],
        ['label' => 'Gestion des visiteurs', 'route' => 'school-life.visitors.index', 'icon' => 'users', 'active_routes' => ['school-life.visitors.*'], 'section' => 'Suivi'],
        ['label' => 'Demandes de documents', 'route' => 'school-life.document-requests.index', 'icon' => 'book', 'active_routes' => ['school-life.document-requests.*'], 'section' => 'Suivi'],
        ['label' => 'Réclamations', 'route' => 'school-life.feedback-cases.index', 'icon' => 'message', 'active_routes' => ['school-life.feedback-cases.*'], 'section' => 'Suivi'],
        ['label' => 'Scan QR', 'route' => 'attendance.scan.page', 'icon' => 'shield', 'active_routes' => ['attendance.scan.page'], 'section' => 'Suivi'],
        ['label' => 'Notes', 'route' => 'school-life.grades.index', 'icon' => 'chart', 'section' => 'Suivi'],
        ['label' => 'Devoirs', 'route' => 'school-life.homeworks.index', 'icon' => 'clipboard', 'active_routes' => ['school-life.homeworks.*'], 'section' => 'Suivi'],
        ['label' => 'Recuperations', 'route' => 'school-life.pickup-requests.index', 'icon' => 'calendar', 'section' => 'Suivi'],
        ['label' => 'Paiements Événements', 'route' => 'school-life.finance.events.index', 'icon' => 'wallet', 'active_routes' => ['school-life.finance.events.*'], 'section' => 'Suivi'],
        ['label' => 'Matières', 'route' => 'school-life.subjects.index', 'icon' => 'book', 'section' => 'Organisation'],
        ['label' => 'Emploi du temps', 'route' => 'school-life.timetable.index', 'icon' => 'calendar', 'section' => 'Organisation'],
        ['label' => 'Agenda', 'route' => 'school-life.events.index', 'icon' => 'calendar', 'section' => 'Organisation'],
        ['label' => 'Activités', 'route' => 'school-life.activities.index', 'icon' => 'calendar', 'active_routes' => ['school-life.activities.*'], 'section' => 'Organisation'],
        ['label' => 'Transport', 'route' => 'transport.ops.index', 'icon' => 'users', 'active_routes' => ['transport.ops.*'], 'section' => 'Organisation'],
        ['label' => 'Actualites', 'route' => 'school-life.news.index', 'icon' => 'message', 'active_routes' => ['school-life.news.*'], 'section' => 'Communication'],
        ['label' => 'Rendez-vous', 'route' => 'school-life.appointments.index', 'icon' => 'calendar', 'section' => 'Communication'],
    ];

    if (Route::has('school-life.notifications.index')) {
        $nav[] = ['label' => 'Notifications', 'route' => 'school-life.notifications.index', 'icon' => 'bell', 'section' => 'Communication'];
    }
    if (Route::has('school-life.messages.index')) {
        $nav[] = ['label' => 'Messagerie', 'route' => 'school-life.messages.index', 'icon' => 'message', 'section' => 'Communication'];
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
                    $currentSchool?->name ?? 'École active',
                    $activeNav['label'] ?? 'Suivi',
                ]"
                summary-title="Module actif"
                :summary-value="$activeNav['label'] ?? 'Vie scolaire'"
                summary-copy="Accès operationnel unifie pour le suivi des Élèves, les présences, les devoirs, les rendez-vous et l emploi du temps."
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
