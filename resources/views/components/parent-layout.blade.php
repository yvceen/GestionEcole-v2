@props([
    'title' => 'Espace parent',
    'subtitle' => 'Retrouvez en quelques clics les informations essentielles de vos enfants, sur ordinateur comme sur mobile.',
])

@php
    $nav = [
        ['label' => 'Accueil', 'route' => 'parent.dashboard', 'icon' => 'home', 'active_routes' => ['parent.dashboard']],
        [
            'label' => 'Enfants',
            'route' => 'parent.children.index',
            'icon' => 'users',
            'active_routes' => ['parent.children.*', 'parent.courses.*', 'parent.homeworks.*', 'parent.grades.*', 'parent.timetable.*'],
        ],
        ['label' => 'Absences', 'route' => 'parent.attendance.index', 'icon' => 'calendar-check', 'active_routes' => ['parent.attendance.*']],
        ['label' => 'Santé', 'route' => 'parent.health.index', 'icon' => 'heart', 'active_routes' => ['parent.health.*']],
        ['label' => 'Autorisations', 'route' => 'parent.digital-authorizations.index', 'icon' => 'clipboard', 'active_routes' => ['parent.digital-authorizations.*']],
        ['label' => 'Documents', 'route' => 'parent.document-requests.index', 'icon' => 'file', 'active_routes' => ['parent.document-requests.*']],
        ['label' => 'Réclamations', 'route' => 'parent.feedback-cases.index', 'icon' => 'message', 'active_routes' => ['parent.feedback-cases.*']],
        ['label' => 'Agenda', 'route' => 'parent.events.index', 'icon' => 'calendar', 'active_routes' => ['parent.events.*', 'parent.calendar.*']],
        ['label' => 'Activités', 'route' => 'parent.activities.index', 'icon' => 'spark', 'active_routes' => ['parent.activities.*']],
        ['label' => 'Transport', 'route' => 'parent.transport.index', 'icon' => 'bus', 'active_routes' => ['parent.transport.*']],
        ['label' => 'Paiements', 'route' => 'parent.finance.index', 'icon' => 'wallet', 'active_routes' => ['parent.finance.*']],
    ];

    $requestLink = null;
    if (\Illuminate\Support\Facades\Route::has('parent.pickup-requests.index')) {
        $requestLink = [
            'label' => 'Demandes',
            'route' => 'parent.pickup-requests.index',
            'icon' => 'hand',
            'active_routes' => ['parent.pickup-requests.*', 'parent.appointments.*'],
        ];
    } elseif (\Illuminate\Support\Facades\Route::has('parent.appointments.create')) {
        $requestLink = [
            'label' => 'Demandes',
            'route' => 'parent.appointments.create',
            'icon' => 'hand',
            'active_routes' => ['parent.appointments.*'],
        ];
    }

    if ($requestLink) {
        $nav[] = $requestLink;
    }

    if (\Illuminate\Support\Facades\Route::has('parent.cards.index')) {
        $nav[] = [
            'label' => 'Cartes',
            'route' => 'parent.cards.index',
            'icon' => 'card',
            'active_routes' => ['parent.cards.*'],
        ];
    }

    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);

    $children = \App\Models\Student::query()
        ->with(['classroom.level'])
        ->where('school_id', app()->bound('current_school_id') ? (int) app('current_school_id') : 0)
        ->where('parent_user_id', auth()->id())
        ->orderBy('full_name')
        ->get();

    $parentName = auth()->user()?->name ?? 'Parent';

    $headerActions = [
        ['label' => 'Voir mes enfants', 'route' => 'parent.children.index'],
        ['label' => 'Suivre les absences', 'route' => 'parent.attendance.index'],
        ['label' => 'Activités et transport', 'route' => 'parent.activities.index'],
        ['label' => 'Voir les paiements', 'route' => 'parent.finance.index'],
    ];

    if ($requestLink) {
        array_unshift($headerActions, ['label' => 'Envoyer une demande', 'route' => $requestLink['route']]);
        $headerActions = array_slice($headerActions, 0, 3);
    }

    $mobileDockLinks = [
        ['label' => 'Accueil', 'route' => 'parent.dashboard', 'icon' => 'home', 'active_routes' => ['parent.dashboard']],
        ['label' => 'Enfants', 'route' => 'parent.children.index', 'icon' => 'users', 'active_routes' => ['parent.children.*', 'parent.courses.*', 'parent.homeworks.*', 'parent.grades.*', 'parent.timetable.*']],
        ['label' => 'Absences', 'route' => 'parent.attendance.index', 'icon' => 'calendar-check', 'active_routes' => ['parent.attendance.*']],
        ['label' => 'Paiements', 'route' => 'parent.finance.index', 'icon' => 'wallet', 'active_routes' => ['parent.finance.*']],
    ];
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
    <x-app-shell :links="$nav" :mobile-dock-links="$mobileDockLinks" navigation-title="Portail parent">
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
                eyebrow="Portail parent"
                :title="$title"
                :subtitle="$subtitle"
                :badges="[
                    $parentName,
                    $children->count().' enfant(s)',
                    $currentSchool?->name ?? 'École active',
                ]"
                summary-title="Suivi famille"
                summary-value="Les infos utiles, sans detour"
                summary-copy="Absences, paiements, demandes et suivi de chaque enfant sont accèssibles depuis une navigation plus simple."
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
