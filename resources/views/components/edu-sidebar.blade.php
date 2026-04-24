@props([
    'nav' => [],
    'heading' => 'Navigation',
    'label' => 'Navigation',
    'tip' => null,
    'user' => null,
])

@php
    use Illuminate\Support\Facades\Route;
@endphp

<div class="edu-sidebar-root" data-edu-sidebar-root data-edu-sidebar-open="false">
    <div class="edu-sidebar-overlay" data-edu-sidebar-overlay aria-hidden="true"></div>
    <div class="edu-sidebar-panel app-sidebar-panel p-4" data-edu-sidebar-panel role="navigation" aria-label="{{ $heading }}">
        <div class="border-b border-slate-200 pb-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="app-overline">{{ $label }}</p>
                    <p class="mt-2 text-lg font-semibold leading-tight text-slate-900">{{ $heading }}</p>
                </div>
                <button type="button" class="app-button-ghost min-h-10 rounded-full px-3 lg:hidden" data-edu-sidebar-close aria-label="Fermer la navigation">
                    Fermer
                </button>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3">
            <x-school-logo :size="44" />
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">{{ $user?->name ?? 'Utilisateur' }}</div>
                <span class="text-xs capitalize text-slate-500">{{ $user?->role ?? 'Équipe éducative' }}</span>
            </div>
        </div>

        <nav class="mt-4 space-y-1.5" aria-label="{{ $heading }} liens">
            @foreach ($nav as $item)
                @php
                    $routeName = $item['route'] ?? null;
                    $hasRoute = $routeName && Route::has($routeName);
                    $isActive = $item['active'] ?? false;
                    $classes = 'app-sidebar-link' . ($isActive ? ' app-sidebar-link-active' : '');
                @endphp

                @if ($hasRoute)
                    <a href="{{ route($routeName) }}" class="{{ $classes }}">
                        <span class="app-sidebar-icon-wrap">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $item['label'] }}</span>
                    </a>
                @else
                    <div class="{{ $classes }} opacity-60">
                        <span class="app-sidebar-icon-wrap">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ $item['label'] }}</span>
                    </div>
                @endif
            @endforeach
        </nav>

        @if ($tip)
            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-500">
                {{ $tip }}
            </div>
        @endif
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-edu-sidebar-root]');
            if (!root) return;

            const overlay = root.querySelector('[data-edu-sidebar-overlay]');
            const closeBtn = root.querySelector('[data-edu-sidebar-close]');
            const openBtns = document.querySelectorAll('[data-edu-sidebar-toggle-target="eduNav"]');

            const setOpen = (value) => root.setAttribute('data-edu-sidebar-open', value ? 'true' : 'false');
            const close = () => setOpen(false);

            openBtns.forEach((btn) => btn.addEventListener('click', () => setOpen(true)));
            overlay?.addEventListener('click', close);
            closeBtn?.addEventListener('click', close);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') close();
            });
        });
    </script>
@endonce
