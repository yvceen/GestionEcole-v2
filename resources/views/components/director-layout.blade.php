@props([
    'title' => 'Espace Directeur',
    'subtitle' => 'Pilotez les indicateurs, les suivis pedagogiques et les arbitrages de direction dans un shell aligne sur le portail parent.',
])

@php
    use Illuminate\Support\Facades\Route;

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'director.dashboard', 'icon' => 'home', 'section' => 'Vue generale'],
        ['label' => 'Suivi', 'route' => 'director.monitoring', 'icon' => 'chart', 'section' => 'Suivi'],
        ['label' => 'Presences', 'route' => 'director.attendance.index', 'icon' => 'shield', 'section' => 'Suivi'],
        ['label' => 'Devoirs', 'route' => 'director.homeworks.index', 'icon' => 'clipboard', 'section' => 'Suivi'],
        ['label' => 'Eleves', 'route' => 'director.students.index', 'icon' => 'user', 'section' => 'Gestion'],
        ['label' => 'Parents', 'route' => 'director.parents.index', 'icon' => 'users', 'section' => 'Gestion'],
        ['label' => 'Enseignants', 'route' => 'director.teachers.index', 'icon' => 'users', 'section' => 'Gestion'],
        ['label' => 'Matieres', 'route' => 'director.subjects.index', 'icon' => 'book', 'section' => 'Organisation'],
        ['label' => 'Emploi du temps', 'route' => 'director.timetable.index', 'icon' => 'calendar', 'section' => 'Organisation'],
        ['label' => 'Agenda', 'route' => 'director.events.index', 'icon' => 'calendar', 'section' => 'Organisation'],
        ['label' => 'Activites', 'route' => 'director.activities.index', 'icon' => 'calendar', 'section' => 'Organisation'],
        ['label' => 'Rendez-vous', 'route' => 'director.appointments.index', 'icon' => 'calendar', 'section' => 'Communication'],
        ['label' => 'Actualites', 'route' => 'director.news.index', 'icon' => 'message', 'section' => 'Communication'],
    ];

    if (Route::has('director.results.index')) {
        $nav[] = ['label' => 'Resultats', 'route' => 'director.results.index', 'icon' => 'chart', 'section' => 'Suivi'];
    }
    if (Route::has('director.support.index')) {
        $nav[] = ['label' => 'Soutien', 'route' => 'director.support.index', 'icon' => 'shield', 'section' => 'Suivi'];
    }
    if (Route::has('director.councils.index')) {
        $nav[] = ['label' => 'Conseils', 'route' => 'director.councils.index', 'icon' => 'calendar', 'section' => 'Organisation'];
    }
    if (Route::has('director.reports.index')) {
        $nav[] = ['label' => 'Rapports', 'route' => 'director.reports.index', 'icon' => 'clipboard', 'section' => 'Suivi'];
    }
    if (Route::has('director.documents.registration-requirements.index')) {
        $nav[] = ['label' => 'Documents', 'route' => 'director.documents.registration-requirements.index', 'icon' => 'clipboard', 'section' => 'Communication'];
    }
    if (Route::has('director.messages.index')) {
        $nav[] = ['label' => 'Messagerie', 'route' => 'director.messages.index', 'icon' => 'message', 'section' => 'Communication'];
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
    <x-app-shell :links="$nav" navigation-title="Portail direction">
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
                eyebrow="Portail direction"
                :title="$title"
                :subtitle="$subtitle"
                :badges="[
                    auth()->user()?->name ?? 'Direction',
                    $currentSchool?->name ?? 'Ecole active',
                    $activeNav['label'] ?? 'Pilotage',
                ]"
                summary-title="Vue active"
                :summary-value="$activeNav['label'] ?? 'Direction'"
                summary-copy="Les vues de suivi, de resultats et de coordination gardent la meme structure de navigation et le meme rythme visuel."
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
