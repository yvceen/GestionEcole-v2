@props([
    'title' => 'Accueil',
    'subtitle' => 'Reception, visiteurs, demandes et orientation des familles.',
])

@php
    use Illuminate\Support\Facades\Route;

    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'accueil.dashboard', 'icon' => 'home', 'section' => 'Accueil'],
        ['label' => 'Gestion des visiteurs', 'route' => 'accueil.visitors.index', 'icon' => 'users', 'active_routes' => ['accueil.visitors.*'], 'section' => 'Operations'],
        ['label' => 'Demandes de documents', 'route' => 'accueil.document-requests.index', 'icon' => 'book', 'active_routes' => ['accueil.document-requests.*'], 'section' => 'Operations'],
        ['label' => 'Reclamations', 'route' => 'accueil.feedback-cases.index', 'icon' => 'message', 'active_routes' => ['accueil.feedback-cases.*'], 'section' => 'Suivi'],
    ];

    if (Route::has('accueil.notifications.index')) {
        $nav[] = ['label' => 'Notifications', 'route' => 'accueil.notifications.index', 'icon' => 'bell', 'section' => 'Suivi'];
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
    <x-app-shell :links="$nav" navigation-title="Accueil">
        <div x-data="portalShell()" data-portal-shell class="ui-scope portal-space space-y-6">
            <div x-cloak x-show="navigating" x-transition.opacity class="portal-loading-overlay" aria-live="polite" aria-busy="true">
                <div class="portal-loading-card">
                    <span class="portal-spinner" aria-hidden="true"></span>
                    <p class="text-sm font-semibold text-slate-900" x-text="loadingLabel"></p>
                </div>
            </div>

            <div x-cloak x-show="navigating" class="portal-loading-bar"></div>

            <x-portal-header
                eyebrow="Accueil"
                :title="$title"
                :subtitle="$subtitle"
                :badges="[
                    auth()->user()?->name ?? 'Accueil',
                    $currentSchool?->name ?? 'Ecole active',
                    $activeNav['label'] ?? 'Reception',
                ]"
                summary-title="Module actif"
                :summary-value="$activeNav['label'] ?? 'Accueil'"
                summary-copy="Espace clair pour recevoir les familles, suivre les visiteurs et orienter les demandes sans acces sensible."
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
