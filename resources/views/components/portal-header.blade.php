@props([
    'eyebrow' => 'Portail',
    'title' => '',
    'subtitle' => null,
    'badges' => [],
    'summaryTitle' => null,
    'summaryValue' => null,
    'summaryCopy' => null,
    'nav' => [],
    'actions' => [],
])

@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $badgeItems = collect($badges)
        ->filter(fn ($item) => filled($item))
        ->values();

    $navItems = collect($nav)
        ->filter(fn ($item) => is_array($item) && !empty($item['route']) && Route::has($item['route']))
        ->values();

    $actionItems = collect($actions)
        ->filter(fn ($item) => is_array($item) && !empty($item['route']) && Route::has($item['route']))
        ->take(3)
        ->values();
@endphp

<section class="portal-hero">
    <div class="portal-hero-content">
        <div class="min-w-0">
            <div class="portal-heading-row">
                <div class="portal-title-mark" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="4" y="4" width="6" height="6" rx="1.5"/>
                        <rect x="14" y="4" width="6" height="6" rx="1.5"/>
                        <rect x="4" y="14" width="6" height="6" rx="1.5"/>
                        <rect x="14" y="14" width="6" height="6" rx="1.5"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="portal-eyebrow">{{ $eyebrow }}</p>
                    <h1 class="portal-hero-title">{{ $title }}</h1>
                </div>
            </div>

            @if($subtitle)
                <p class="portal-hero-copy">{{ $subtitle }}</p>
            @endif

            @if($badgeItems->isNotEmpty())
                <div class="portal-context-row">
                    @foreach($badgeItems as $badge)
                        <span class="portal-chip">{{ $badge }}</span>
                    @endforeach
                </div>
            @endif

            @if($actionItems->isNotEmpty())
                <div class="portal-hero-actions">
                    @foreach($actionItems as $item)
                        <a
                            href="{{ route((string) $item['route']) }}"
                            data-loading-label="Ouverture de {{ Str::lower((string) ($item['label'] ?? 'la page')) }}..."
                            class="portal-hero-action"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if($summaryTitle || $summaryValue || $summaryCopy)
            <div class="portal-hero-aside">
                <div class="portal-mini-panel">
                    @if($summaryTitle)
                        <p class="portal-mini-label">{{ $summaryTitle }}</p>
                    @endif
                    @if($summaryValue)
                        <p class="portal-mini-value">{{ $summaryValue }}</p>
                    @endif
                    @if($summaryCopy)
                        <p class="portal-mini-copy">{{ $summaryCopy }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($navItems->isNotEmpty())
        <div class="portal-top-nav">
            @foreach($navItems as $item)
                @php
                    $route = (string) $item['route'];
                    $activeRoutes = collect($item['active_routes'] ?? [])
                        ->filter(fn ($pattern) => is_string($pattern) && $pattern !== '')
                        ->values();
                    $active = request()->routeIs($route)
                        || request()->routeIs(str_replace('.index', '.*', $route))
                        || $activeRoutes->contains(fn ($pattern) => request()->routeIs($pattern));
                @endphp
                <a
                    href="{{ route($route) }}"
                    data-loading-label="Ouverture de {{ Str::lower((string) ($item['label'] ?? 'la page')) }}..."
                    class="portal-top-nav-link {{ $active ? 'portal-top-nav-link-active' : '' }}"
                >
                    {{ $item['label'] ?? 'Module' }}
                </a>
            @endforeach
        </div>
    @endif
</section>
