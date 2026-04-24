@props([
    'links' => null,
    'navigationTitle' => 'Navigation principale',
    'mobileDockLinks' => null,
])

@php
    use App\Models\AppNotification;
    use App\Models\Message;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Schema;

    $user = auth()->user();
    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);
    $schoolName = $currentSchool?->name ?? 'MyEdu';
    $roleLabel = match ($user?->role) {
        'admin' => 'Administration',
        'director' => 'Direction',
        'teacher' => 'Enseignement',
        'parent' => 'Parents',
        'student' => 'Eleves',
        'school_life' => 'Vie scolaire',
        'super_admin' => 'Super administration',
        default => 'Espace',
    };

    $defaultLinks = [];
    if (Route::has('dashboard')) {
        $defaultLinks[] = ['label' => 'Tableau de bord', 'route' => 'dashboard'];
    }

    if ($user) {
        $roleLinks = match ($user->role) {
            'admin' => [
                ['label' => 'Tableau de bord', 'route' => 'admin.dashboard'],
                ['label' => 'Structure', 'route' => 'admin.structure.index'],
                ['label' => 'Eleves', 'route' => 'admin.students.index'],
                ['label' => 'Utilisateurs', 'route' => 'admin.users.index'],
                ['label' => 'Finance', 'route' => 'admin.finance.index'],
                ['label' => 'Matieres', 'route' => 'admin.subjects.index'],
                ['label' => 'Devoirs', 'route' => 'admin.homeworks.index'],
                ['label' => 'Pedagogie enseignants', 'route' => 'admin.teachers.pedagogy'],
                ['label' => 'Actualites', 'route' => 'admin.news.index'],
                ['label' => 'Rendez-vous', 'route' => 'admin.appointments.index'],
                ['label' => 'Vie scolaire', 'route' => 'admin.school-life.index'],
                ['label' => 'Messagerie', 'route' => 'admin.messages.index'],
                ['label' => 'Emploi du temps', 'route' => 'admin.timetable.index'],
                ['label' => 'Transport scolaire', 'route' => 'admin.transport.index'],
                ['label' => 'Notifications', 'route' => 'admin.notifications.index'],
            ],
            'director' => [
                ['label' => 'Eleves', 'route' => 'director.students.index'],
                ['label' => 'Enseignants', 'route' => 'director.teachers.index'],
                ['label' => 'Suivi', 'route' => 'director.monitoring'],
            ],
            'teacher' => [
                ['label' => 'Cours', 'route' => 'teacher.courses.index'],
                ['label' => 'Devoirs', 'route' => 'teacher.homeworks.index'],
                ['label' => 'Messages', 'route' => 'teacher.messages.index'],
                ['label' => 'Notifications', 'route' => 'teacher.notifications.index'],
            ],
            'parent' => [
                ['label' => 'Mes enfants', 'route' => 'parent.children.index'],
                ['label' => 'Cours', 'route' => 'parent.courses.index'],
                ['label' => 'Devoirs', 'route' => 'parent.homeworks.index'],
                ['label' => 'Notifications', 'route' => 'parent.notifications.index'],
            ],
            'student' => [
                ['label' => 'Mes cours', 'route' => 'student.courses.index'],
                ['label' => 'Mes devoirs', 'route' => 'student.homeworks.index'],
                ['label' => 'Notifications', 'route' => 'student.notifications.index'],
            ],
            'school_life' => [
                ['label' => 'Tableau de bord', 'route' => 'school-life.dashboard'],
                ['label' => 'Eleves', 'route' => 'school-life.students.index'],
                ['label' => 'Presences', 'route' => 'school-life.attendance.index'],
                ['label' => 'Devoirs', 'route' => 'school-life.homeworks.index'],
                ['label' => 'Recuperations', 'route' => 'school-life.pickup-requests.index'],
                ['label' => 'Notifications', 'route' => 'school-life.notifications.index'],
            ],
            'super_admin' => [
                ['label' => 'Ecoles', 'route' => 'super.schools.create'],
            ],
            default => [],
        };

        foreach ($roleLinks as $item) {
            if (Route::has($item['route'])) {
                $defaultLinks[] = $item;
            }
        }
    }

    $resolvedLinks = collect(is_array($links) && count($links) ? $links : $defaultLinks)
        ->filter(fn ($item) => is_array($item) && !empty($item['route']) && Route::has($item['route']))
        ->values();

    $notificationUnreadCount = 0;
    $messageUnreadCount = 0;

    if ($user && Schema::hasTable('notifications')) {
        $notificationUserColumn = Schema::hasColumn('notifications', 'recipient_user_id') ? 'recipient_user_id' : 'user_id';

        if (Schema::hasColumn('notifications', $notificationUserColumn)) {
            $notificationBase = AppNotification::query()->where($notificationUserColumn, $user->id);
            $notificationUnreadCount = (clone $notificationBase)->whereNull('read_at')->count();
            $messageUnreadCount = (clone $notificationBase)
                ->whereNull('read_at')
                ->where('type', 'message')
                ->count();
        }
    }

    if ($user && $user->role === 'admin' && Schema::hasTable('messages')) {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) ($user->school_id ?? 0);
        if ($schoolId > 0 && Schema::hasColumn('messages', 'status') && Schema::hasColumn('messages', 'school_id')) {
            $messageUnreadCount = max(
                $messageUnreadCount,
                Message::query()->where('school_id', $schoolId)->where('status', 'pending')->count()
            );
        }
    }

    $resolvedLinks = $resolvedLinks->map(function ($item) use ($notificationUnreadCount, $messageUnreadCount) {
        $route = (string) ($item['route'] ?? '');

        if (str_ends_with($route, '.messages.index') && $messageUnreadCount > 0) {
            $item['badge'] = $messageUnreadCount > 99 ? '99+' : (string) $messageUnreadCount;
        }

        if (str_ends_with($route, '.notifications.index') && $notificationUnreadCount > 0) {
            $item['badge'] = $notificationUnreadCount > 99 ? '99+' : (string) $notificationUnreadCount;
        }

        return $item;
    })->values();

    $sidebarIcons = [
        'menu' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />',
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 11.5 12 4l9 7.5" /><path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5V20h14v-9.5" />',
        'book' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5A2.5 2.5 0 0 1 7.5 3H19v16H7.5A2.5 2.5 0 0 0 5 21.5V5.5Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8" />',
        'clipboard' => '<rect x="6" y="4" width="12" height="16" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5h6M9 9h6M9 13h6" />',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v4M16 3v4M4 10h16" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 19V9m7 10V5m7 14v-7" />',
        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-2.7 8.6-7 10-4.3-1.4-7-5.5-7-10V6l7-3Z" />',
        'wallet' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6.5A2.5 2.5 0 0 1 4 16.5v-9Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12h5M16 12h.01" />',
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 19a4 4 0 0 0-8 0" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 11a3 3 0 1 0-3-3 3 3 0 0 0 3 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19 19a3 3 0 0 0-3-2.8M17 10.5a2.5 2.5 0 1 0-1.1-4.8M5 19a3 3 0 0 1 3-2.8M7 10.5A2.5 2.5 0 1 1 8.1 5.7" />',
        'message' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v9H8l-4 4V6Z" />',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" /><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a3 3 0 0 0 6 0" />',
    ];

    $renderableLinks = $resolvedLinks->map(function ($item) use ($sidebarIcons) {
        $route = (string) ($item['route'] ?? '');
        $routePattern = str_ends_with($route, '.index') ? str_replace('.index', '.*', $route) : null;
        $activeRoutes = collect($item['active_routes'] ?? [])
            ->filter(fn ($pattern) => is_string($pattern) && $pattern !== '')
            ->values();
        $icon = (string) ($item['icon'] ?? 'menu');
        $badge = $item['badge'] ?? null;

        return [
            'label' => (string) ($item['label'] ?? 'Module'),
            'url' => route($route),
            'active' => request()->routeIs($route)
                || ($routePattern ? request()->routeIs($routePattern) : false)
                || $activeRoutes->contains(fn ($pattern) => request()->routeIs($pattern)),
            'badge' => filled($badge) ? (string) $badge : null,
            'icon_svg' => $sidebarIcons[$icon] ?? $sidebarIcons['menu'],
        ];
    })->values();

    $dockSource = collect(is_array($mobileDockLinks) && count($mobileDockLinks) ? $mobileDockLinks : []);
    $shouldShowMobileDock = $user && in_array((string) $user->role, ['parent', 'student'], true);

    $mobileDockItems = ($dockSource->isNotEmpty() ? $dockSource : $renderableLinks->take(4))
        ->filter(fn ($item) => is_array($item) && !empty($item['url']) && !empty($item['label']) && !empty($item['icon_svg']))
        ->take(4)
        ->values();
@endphp

<div
    x-data="{
        mobileOpen: false,
        closeSidebar() { this.mobileOpen = false; },
        toggleSidebar() { this.mobileOpen = !this.mobileOpen; },
    }"
    @toggle-mobile-sidebar.window="toggleSidebar()"
    @close-mobile-sidebar.window="closeSidebar()"
    @keydown.escape.window="closeSidebar()"
    x-effect="
        $dispatch('mobile-sidebar-changed', { open: mobileOpen });
        document.body.classList.toggle('overflow-hidden', mobileOpen);
    "
    class="app-shell-body flex min-h-screen flex-col"
>
    <x-app-navbar :links="$resolvedLinks->all()" :navigation-title="$navigationTitle" />

    <div
        x-cloak
        x-show="mobileOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-sm lg:hidden"
        @click="closeSidebar()"
        aria-hidden="true"
    ></div>

    <div class="flex flex-1">
        <aside class="hidden shrink-0 lg:block lg:w-[var(--sidebar-w)]">
            <div class="sticky top-[calc(var(--navbar-h)+1rem)] p-4 pr-0">
                <div class="app-sidebar-panel p-4">
                    <div class="border-b border-slate-200 px-2 pb-4">
                        <p class="app-overline">Navigation</p>
                        <div class="mt-3 flex items-start gap-3">
                            <div class="app-sidebar-brand-icon">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 12.5L12 5l8 7.5v6A1.5 1.5 0 0 1 18.5 20H5.5A1.5 1.5 0 0 1 4 18.5v-6Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20v-5h6v5" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $navigationTitle }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $schoolName }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="app-sidebar-meta-pill">{{ $roleLabel }}</span>
                            <span class="app-sidebar-meta-pill">{{ $user?->name ?? 'Utilisateur' }}</span>
                        </div>
                    </div>

                    <nav class="mt-4 space-y-1.5" aria-label="{{ $navigationTitle }}">
                        @forelse($renderableLinks as $item)
                            <a href="{{ $item['url'] }}" class="app-sidebar-link {{ $item['active'] ? 'app-sidebar-link-active' : '' }}">
                                <span class="app-sidebar-icon-wrap">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        {!! $item['icon_svg'] !!}
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                @if($item['badge'])
                                    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        {{ $item['badge'] }}
                                    </span>
                                @endif
                            </a>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-3 py-4 text-sm text-slate-500">
                            Aucun module disponible.
                        </p>
                    @endforelse
                    </nav>

                    <div class="mt-4 border-t border-slate-200 px-2 pt-4">
                        <div class="app-sidebar-footer">
                            <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Session active</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user?->name ?? 'Utilisateur' }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $schoolName }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <aside
            id="app-mobile-sidebar"
            class="fixed left-0 top-0 z-50 h-full w-[var(--sidebar-w)] max-w-[88vw] -translate-x-full overflow-hidden rounded-r-[28px] border-r border-slate-200 bg-white shadow-[0_30px_80px_-45px_rgba(15,23,42,0.28)] transition-transform duration-300 ease-out lg:hidden scrollbar-hidden"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <p class="app-overline">Navigation</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $navigationTitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="app-button-ghost min-h-10 rounded-full px-3"
                        @click="closeSidebar()"
                        aria-label="Fermer le menu"
                    >
                        Fermer
                    </button>
                </div>

                <nav class="flex-1 space-y-1.5 overflow-y-auto px-3 py-4" aria-label="{{ $navigationTitle }}">
                    @forelse($renderableLinks as $item)
                        <a href="{{ $item['url'] }}"
                           class="app-sidebar-link {{ $item['active'] ? 'app-sidebar-link-active' : '' }}"
                           @click="closeSidebar()">
                            <span class="app-sidebar-icon-wrap">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    {!! $item['icon_svg'] !!}
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                            @if($item['badge'])
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-3 py-4 text-sm text-slate-500">
                            Aucun module disponible.
                        </p>
                    @endforelse
                </nav>

                <div class="border-t border-slate-200 px-5 py-4">
                    <div class="app-sidebar-footer">
                        <p class="text-[0.72rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Session active</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user?->name ?? 'Utilisateur' }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $schoolName }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="app-main min-w-0 flex-1 overflow-x-hidden {{ $shouldShowMobileDock && $mobileDockItems->isNotEmpty() ? 'app-main-has-dock' : '' }}">
            <div class="ui-container">
                {{ $slot }}
            </div>
        </main>
    </div>

    @if($shouldShowMobileDock && $mobileDockItems->isNotEmpty())
        <nav class="portal-mobile-dock lg:hidden" aria-label="Navigation rapide">
            @foreach($mobileDockItems as $item)
                <a href="{{ $item['url'] }}" class="portal-mobile-dock-link {{ $item['active'] ? 'portal-mobile-dock-link-active' : '' }}">
                    <span class="portal-mobile-dock-icon" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            {!! $item['icon_svg'] !!}
                        </svg>
                    </span>
                    <span class="portal-mobile-dock-label">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <button type="button" class="portal-mobile-dock-link" @click="toggleSidebar()" aria-controls="app-mobile-sidebar" :aria-expanded="mobileOpen ? 'true' : 'false'">
                <span class="portal-mobile-dock-icon" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        {!! $sidebarIcons['menu'] !!}
                    </svg>
                </span>
                <span class="portal-mobile-dock-label">Menu</span>
            </button>
        </nav>
    @endif
</div>
