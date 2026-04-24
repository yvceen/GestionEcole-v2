@props([
    'title' => 'Super administration',
    'subtitle' => null,
])

@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $pageSubtitle = $subtitle ?: 'Pilotage centralise des ecoles, des administrateurs et de l activite globale.';
    $navGroups = [
        [
            'label' => 'Vue generale',
            'items' => [
                [
                    'label' => 'Tableau de bord',
                    'route' => 'super.dashboard',
                    'patterns' => ['super.dashboard'],
                    'icon' => 'dashboard',
                    'description' => 'Indicateurs et activite',
                ],
            ],
        ],
        [
            'label' => 'Gestion',
            'items' => [
                [
                    'label' => 'Ecoles',
                    'route' => 'super.schools.index',
                    'patterns' => ['super.schools.index', 'super.schools.edit'],
                    'icon' => 'school',
                    'description' => 'Parcourir et administrer',
                ],
                [
                    'label' => 'Nouvelle ecole',
                    'route' => 'super.schools.create',
                    'patterns' => ['super.schools.create'],
                    'icon' => 'plus',
                    'description' => 'Ajouter un nouvel espace',
                ],
            ],
        ],
    ];
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MyEdu</title>
    <x-school-favicons />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="super-shell min-h-screen overflow-x-hidden">
    <div x-data="{ mobileOpen: false }" class="min-h-screen">
        <div
            x-cloak
            x-show="mobileOpen"
            x-transition.opacity
            class="fixed inset-0 z-40 bg-slate-950/35 backdrop-blur-sm lg:hidden"
            @click="mobileOpen = false"
            aria-hidden="true"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 w-[min(88vw,20rem)] -translate-x-full border-r border-slate-200/80 bg-white/95 px-4 py-5 shadow-[0_30px_80px_-45px_rgba(15,23,42,0.34)] transition-transform duration-300 lg:w-[19.5rem] lg:translate-x-0 lg:border-r-0 lg:bg-transparent lg:px-5 lg:py-6 lg:shadow-none"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="super-sidebar h-full">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-5">
                    <div>
                        <p class="super-eyebrow">Super Admin</p>
                        <h1 class="mt-2 text-lg font-semibold text-slate-950">MyEdu Control</h1>
                        <p class="mt-1 text-sm leading-6 text-slate-500">Gestion centralisee des ecoles et de leurs administrateurs.</p>
                    </div>

                    <button
                        type="button"
                        class="app-button-ghost min-h-10 rounded-full px-3 lg:hidden"
                        @click="mobileOpen = false"
                        aria-label="Fermer le menu"
                    >
                        Fermer
                    </button>
                </div>

                <div class="mt-5 space-y-6">
                    @foreach($navGroups as $group)
                        <div>
                            <p class="super-nav-label">{{ $group['label'] }}</p>

                            <nav class="mt-2 space-y-2" aria-label="{{ $group['label'] }}">
                                @foreach($group['items'] as $item)
                                    @php
                                        $active = request()->routeIs(...($item['patterns'] ?? [$item['route']]));
                                    @endphp

                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="super-nav-link {{ $active ? 'super-nav-link-active' : '' }}"
                                        @click="mobileOpen = false"
                                    >
                                        <span class="super-nav-icon">
                                            @switch($item['icon'])
                                                @case('dashboard')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 13h7V4H4zm9 7h7v-9h-7zm0-11h7V4h-7zM4 20h7v-5H4z"/>
                                                    </svg>
                                                    @break
                                                @case('school')
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 5l9 5.5M5 9.8V20h14V9.8M9 20v-5h6v5M8 12h.01M12 12h.01M16 12h.01"/>
                                                    </svg>
                                                    @break
                                                @default
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                                                    </svg>
                                            @endswitch
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold">{{ $item['label'] }}</span>
                                            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $item['description'] }}</span>
                                        </span>
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    @endforeach
                </div>

                <div class="mt-auto rounded-[24px] border border-slate-200/80 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Session</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Profil super administrateur. Les actions prennent effet sur toute la plateforme.</p>
                </div>
            </div>
        </aside>

        <div class="lg:pl-[21.5rem]">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
                <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="app-button-secondary h-11 w-11 rounded-2xl px-0 lg:hidden"
                            @click="mobileOpen = true"
                            aria-label="Ouvrir le menu"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18"/>
                            </svg>
                        </button>

                        <div class="min-w-0">
                            <p class="super-eyebrow">Pilotage plateforme</p>
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $title }}</p>
                            <p class="hidden text-sm text-slate-500 md:block">{{ $pageSubtitle }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        @if(Route::has('super.schools.create'))
                            <x-ui.button :href="route('super.schools.create')" variant="primary" size="sm">
                                Nouvelle ecole
                            </x-ui.button>
                        @endif

                        @if(Route::has('profile.edit'))
                            <x-ui.button :href="route('profile.edit')" variant="secondary" size="sm">
                                Profil
                            </x-ui.button>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm">
                                Deconnexion
                            </x-ui.button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="space-y-6">
                    @if(session('success'))
                        <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
