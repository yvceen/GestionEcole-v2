@props([
    'title' => 'Administration',
    'subtitle' => null,
])

@php
    use Illuminate\Support\Facades\Route;
    use App\Services\AcademicYearService;

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Structure', 'route' => 'admin.structure.index', 'icon' => 'calendar'],
        ['label' => 'Annees scolaires', 'route' => 'admin.academic-years.index', 'icon' => 'calendar'],
        ['label' => 'Promotions', 'route' => 'admin.academic-promotions.index', 'icon' => 'users'],
        ['label' => 'Eleves', 'route' => 'admin.students.index', 'icon' => 'users'],
        ['label' => 'Utilisateurs', 'route' => 'admin.users.index', 'icon' => 'user'],
        ['label' => 'Finance', 'route' => 'admin.finance.index', 'icon' => 'wallet'],
        ['label' => 'Matieres', 'route' => 'admin.subjects.index', 'icon' => 'book'],
        ['label' => 'Devoirs', 'route' => 'admin.homeworks.index', 'icon' => 'clipboard'],
        ['label' => 'Presences', 'route' => 'admin.attendance.index', 'icon' => 'shield'],
        ['label' => 'Reglages presences', 'route' => 'admin.timetable.settings.edit', 'icon' => 'shield'],
        ['label' => 'Agenda', 'route' => 'admin.events.index', 'icon' => 'calendar'],
        ['label' => 'Activites', 'route' => 'admin.activities.index', 'icon' => 'calendar'],
        ['label' => 'Cartes', 'route' => 'admin.cards.index', 'icon' => 'users'],
        ['label' => 'Pedagogie enseignants', 'route' => 'admin.teachers.pedagogy', 'icon' => 'chart'],
        ['label' => 'Actualites', 'route' => 'admin.news.index', 'icon' => 'message'],
        ['label' => 'Rendez-vous', 'route' => 'admin.appointments.index', 'icon' => 'calendar'],
        ['label' => 'Vie scolaire', 'route' => 'admin.school-life.index', 'icon' => 'shield'],
        ['label' => 'Messagerie', 'route' => 'admin.messages.index', 'icon' => 'message'],
        ['label' => 'Documents', 'route' => 'admin.documents.library.index', 'icon' => 'clipboard'],
        ['label' => 'Emploi du temps', 'route' => 'admin.timetable.index', 'icon' => 'calendar'],
        ['label' => 'Transport scolaire', 'route' => 'admin.transport.index', 'icon' => 'users'],
        ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'icon' => 'bell'],
    ];

    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);
    $schoolLabel = $currentSchool?->name
        ?? (app()->bound('current_school_id') && app('current_school_id') ? 'Etablissement #'.app('current_school_id') : 'Espace administration');
    $currentAcademicYear = app()->bound('current_school_id') && app('current_school_id')
        ? app(AcademicYearService::class)->getCurrentYearForSchool((int) app('current_school_id'))
        : null;
    $pageSubtitle = $subtitle ?: 'Pilotez les operations, les parcours administratifs et les interfaces de l etablissement dans un shell unifie.';

    $activeNav = array_values(array_filter(
        $nav,
        fn (array $item) => Route::has($item['route']) && (request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '.*', $item['route'])))
    ));
    $currentModule = $activeNav[0] ?? null;
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $title }} | {{ $currentSchool?->name ?? 'MyEdu' }}</title>
    <x-school-favicons />
    @stack('head')
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="app-shell-body min-h-screen overflow-x-hidden m-0 p-0">
    <x-app-shell :links="$nav" navigation-title="Administration">
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
                eyebrow="Administration"
                :title="$title"
                :subtitle="$pageSubtitle"
                :badges="[
                    $schoolLabel,
                    $currentAcademicYear?->name ?? 'Annee en preparation',
                    auth()->user()?->name ?? 'Administrateur',
                    $currentModule['label'] ?? 'Vue generale',
                ]"
                summary-title="Module actif"
                :summary-value="$currentModule['label'] ?? 'Administration'"
                summary-copy="Navigation claire, actions lisibles, cartes coherentes et formulaires solides sur tous les ecrans."
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
