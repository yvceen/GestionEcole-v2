@props([
    'title' => null,
    'links' => [],
    'navigationTitle' => 'Navigation principale',
])

@php
    use App\Models\AppNotification;
    use App\Models\Message;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $currentSchool = app()->bound('currentSchool')
        ? app('currentSchool')
        : (app()->bound('current_school') ? app('current_school') : null);

    $roleLabel = match ($user?->role) {
        'super_admin' => 'Super administrateur',
        'admin' => 'Administrateur',
        'director' => 'Directeur',
        'teacher' => 'Enseignant',
        'parent' => 'Parent',
        'student' => 'Eleve',
        'school_life' => 'Responsable scolaire',
        default => 'Utilisateur',
    };

    $school = $currentSchool
        ?: ($user?->school_id ? \App\Models\School::find($user->school_id) : null);
    $logoPath = $school?->logo_path ?? null;
    $schoolName = $school?->name ?? 'MyEdu';
    $defaultLogoUrl = asset('images/edulogo.jpg') . '?v=3';
    $schoolLogoUrl = null;

    if (is_string($logoPath) && $logoPath !== '') {
        $trimmedLogoPath = ltrim($logoPath, '/');
        if (Str::startsWith($logoPath, ['http://', 'https://'])) {
            $schoolLogoUrl = $logoPath;
        } elseif (Storage::disk('public')->exists($trimmedLogoPath)) {
            $schoolLogoUrl = asset('storage/' . $trimmedLogoPath);
        }
    }

    $quickLinks = collect(is_array($links) ? $links : [])
        ->filter(fn ($item) => is_array($item) && !empty($item['route']) && Route::has($item['route']))
        ->take(3)
        ->values();

    $roleActions = collect(match ((string) $user?->role) {
        'admin' => [
            ['label' => 'Nouveau devoir', 'route' => 'admin.homeworks.create'],
            ['label' => 'Consulter devoirs', 'route' => 'admin.homeworks.index'],
            ['label' => 'Messagerie', 'route' => 'admin.messages.index'],
        ],
        'teacher' => [
            ['label' => 'Nouveau devoir', 'route' => 'teacher.homeworks.create'],
            ['label' => 'Mes devoirs', 'route' => 'teacher.homeworks.index'],
            ['label' => 'Messages', 'route' => 'teacher.messages.index'],
        ],
        'parent' => [
            ['label' => 'Mes enfants', 'route' => 'parent.children.index'],
            ['label' => 'Absences', 'route' => 'parent.attendance.index'],
            ['label' => 'Paiements', 'route' => 'parent.finance.index'],
            ['label' => 'Demande', 'route' => 'parent.pickup-requests.index'],
            ['label' => 'Rendez-vous', 'route' => 'parent.appointments.create'],
        ],
        'student' => [
            ['label' => 'Emploi du temps', 'route' => 'student.timetable.index'],
            ['label' => 'Mes devoirs', 'route' => 'student.homeworks.index'],
            ['label' => 'Mes notes', 'route' => 'student.grades.index'],
        ],
        'school_life' => [
            ['label' => 'Recuperations', 'route' => 'school-life.pickup-requests.index'],
            ['label' => 'Eleves', 'route' => 'school-life.students.index'],
            ['label' => 'Presences', 'route' => 'school-life.attendance.index'],
        ],
        default => [],
    })->filter(fn ($item) => is_array($item) && !empty($item['route']) && Route::has($item['route']))->values();

    $notificationRoutePrefix = match ((string) $user?->role) {
        'school_life' => 'school-life',
        'admin', 'teacher', 'student', 'parent' => (string) $user->role,
        default => null,
    };
    $messageRoutePrefix = in_array((string) $user?->role, ['admin', 'teacher', 'parent', 'director'], true)
        ? (string) $user->role
        : null;

    $notificationUserColumn = Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'recipient_user_id')
        ? 'recipient_user_id'
        : 'user_id';

    $notifications = collect();
    $notificationUnreadCount = 0;
    $latestUnreadNotificationId = 0;
    $messageUnreadCount = 0;

    if (
        $user
        && Schema::hasTable('notifications')
        && Schema::hasColumn('notifications', $notificationUserColumn)
    ) {
        $notificationQuery = AppNotification::query()->where($notificationUserColumn, $user->id);

        $notificationUnreadCount = (clone $notificationQuery)->whereNull('read_at')->count();
        $latestUnreadNotificationId = (int) ((clone $notificationQuery)->whereNull('read_at')->max('id') ?? 0);
        $messageUnreadCount = (clone $notificationQuery)
            ->whereNull('read_at')
            ->where('type', 'message')
            ->count();

        if ($notificationRoutePrefix && Route::has($notificationRoutePrefix . '.notifications.index')) {
            $notifications = (clone $notificationQuery)->latest('id')->limit(8)->get();
        }
    }

    if (
        $user?->role === 'admin'
        && Schema::hasTable('messages')
        && Schema::hasColumn('messages', 'status')
        && Schema::hasColumn('messages', 'school_id')
    ) {
        $schoolId = app()->bound('current_school_id') ? (int) app('current_school_id') : (int) ($user->school_id ?? 0);
        if ($schoolId > 0) {
            $messageUnreadCount = max(
                $messageUnreadCount,
                Message::query()->where('school_id', $schoolId)->where('status', 'pending')->count()
            );
        }
    }

    $notificationsIndexUrl = $notificationRoutePrefix && Route::has($notificationRoutePrefix . '.notifications.index')
        ? route($notificationRoutePrefix . '.notifications.index')
        : null;
    $messagesIndexUrl = $messageRoutePrefix && Route::has($messageRoutePrefix . '.messages.index')
        ? route($messageRoutePrefix . '.messages.index')
        : null;
@endphp

<header
    x-data="appNavbar({
        unreadCount: {{ (int) $notificationUnreadCount }},
        latestUnreadNotificationId: {{ (int) $latestUnreadNotificationId }},
        messageUnreadCount: {{ (int) $messageUnreadCount }},
        refreshUrl: @js($notificationsIndexUrl ?: $messagesIndexUrl ?: url()->current()),
    })"
    class="sticky top-0 z-50 w-full glass-nav transition-shadow duration-300"
    :class="$store.ui.hasShadow ? 'shadow-[0_18px_42px_-32px_rgba(15,23,42,0.24)]' : ''"
    data-notification-unread="{{ (int) $notificationUnreadCount }}"
    data-notification-latest-id="{{ (int) $latestUnreadNotificationId }}"
    data-message-unread="{{ (int) $messageUnreadCount }}"
>
    <div class="mx-auto flex h-[var(--navbar-h)] max-w-screen-2xl items-center gap-3 px-4 md:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-3 lg:flex-[0_0_auto]">
            <button
                type="button"
                class="app-button-secondary h-10 w-10 rounded-2xl px-0 lg:hidden"
                @click="$dispatch('toggle-mobile-sidebar')"
                @keydown.escape.window="$dispatch('close-mobile-sidebar')"
                aria-label="Ouvrir le menu"
                aria-controls="app-mobile-sidebar"
                :aria-expanded="mobileOpen ? 'true' : 'false'"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
            </button>

            <div class="flex min-w-0 items-center gap-3 rounded-2xl border border-slate-200 bg-white/80 px-3 py-2 shadow-sm">
                <div class="grid h-11 w-11 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
                    <img
                        src="{{ $schoolLogoUrl ?: $defaultLogoUrl }}"
                        class="h-full w-full {{ $schoolLogoUrl ? 'object-contain' : 'object-cover' }}"
                        alt="Logo ecole"
                        onerror="this.onerror=null; this.src='{{ $defaultLogoUrl }}';"
                    />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $schoolName }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $roleLabel }}</p>
                </div>
            </div>
        </div>

        <div class="hidden min-w-0 flex-1 justify-center md:flex">
            <label class="relative w-full max-w-2xl">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a7 7 0 1 0-7 7 7 7 0 0 0 7-7z" />
                    </svg>
                </span>
                <input
                    class="app-input rounded-full border-slate-200 bg-white/90 pl-10 pr-16"
                    type="search"
                    placeholder="Rechercher un eleve, une classe, un utilisateur..."
                    aria-label="Rechercher"
                >
                <span class="pointer-events-none absolute inset-y-0 right-3 hidden items-center rounded-full border border-slate-200 bg-white px-2.5 text-[11px] font-semibold text-slate-500 lg:flex">
                    Ctrl+K
                </span>
            </label>
        </div>

        <div class="flex flex-none items-center gap-2">
            <button
                type="button"
                class="app-button-secondary h-10 w-10 rounded-2xl px-0 md:hidden"
                aria-label="Recherche"
                @click="$dispatch('open-search', {})"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.35-5.15a7 7 0 1 0-7 7 7 7 0 0 0 7-7z" />
                </svg>
            </button>

            @if($quickLinks->isNotEmpty())
                <div class="hidden xl:flex items-center gap-2">
                    @foreach($quickLinks as $item)
                        <a href="{{ route($item['route']) }}" class="app-button-ghost min-h-10 rounded-full px-4 py-2">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif

            @if($roleActions->isNotEmpty())
                <details class="relative hidden lg:block">
                    <summary class="list-none cursor-pointer app-button-secondary min-h-10 rounded-full px-4 py-2">
                        Actions rapides
                    </summary>
                    <div class="absolute right-0 z-50 mt-3 w-60 max-w-[calc(100vw-1rem)] rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_28px_70px_-40px_rgba(15,23,42,0.34)]">
                        @foreach($roleActions as $action)
                            <a href="{{ route($action['route']) }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif

            @if($messagesIndexUrl)
                <a href="{{ $messagesIndexUrl }}" class="app-button-secondary relative h-10 rounded-full px-3" aria-label="Messages">
                    <span class="relative inline-flex">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v9H7l-3 3V6z" />
                        </svg>
                        <template x-if="$store.ui.messageUnreadCount > 0">
                            <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white">
                                <span x-text="$store.ui.messageUnreadCount > 99 ? '99+' : $store.ui.messageUnreadCount"></span>
                            </span>
                        </template>
                    </span>
                </a>
            @endif

            @if($notificationRoutePrefix && Route::has($notificationRoutePrefix . '.notifications.index'))
                <details class="relative">
                    <summary class="list-none cursor-pointer app-button-secondary h-10 rounded-full px-3" aria-label="Notifications">
                        <span class="relative inline-flex">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                            </svg>
                            <template x-if="$store.ui.unreadCount > 0">
                                <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white">
                                    <span x-text="$store.ui.unreadCount > 99 ? '99+' : $store.ui.unreadCount"></span>
                                </span>
                            </template>
                        </span>
                    </summary>
                    <div class="absolute right-0 z-50 mt-3 w-[min(24rem,calc(100vw-1rem))] rounded-2xl border border-slate-200 bg-white p-3 shadow-[0_28px_70px_-40px_rgba(15,23,42,0.34)]">
                        <div class="mb-2 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Notifications</p>
                                <p class="text-xs text-slate-500">Dernieres activites de votre espace.</p>
                            </div>
                            <a href="{{ route($notificationRoutePrefix . '.notifications.index') }}" class="text-xs font-semibold text-sky-700 hover:text-sky-800">
                                Voir tout
                            </a>
                        </div>
                        <div class="max-h-80 space-y-2 overflow-auto">
                            @forelse($notifications as $notification)
                                <a href="{{ route($notificationRoutePrefix . '.notifications.open', $notification) }}" class="block rounded-2xl border border-slate-200 p-3 transition hover:border-slate-300 hover:bg-slate-50">
                                    <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">{{ Str::limit($notification->body, 90) }}</p>
                                    <p class="mt-2 text-[11px] text-slate-500">{{ optional($notification->created_at)->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="rounded-2xl border border-slate-200 p-4 text-sm text-slate-500">Aucune notification.</p>
                            @endforelse
                        </div>
                    </div>
                </details>
            @else
                <button type="button" class="app-button-secondary h-10 rounded-full px-3" aria-label="Notifications">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                    </svg>
                </button>
            @endif

            <details class="relative">
                <summary class="list-none cursor-pointer">
                    <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-2.5 py-1.5 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                        <div class="grid h-9 w-9 place-items-center rounded-full bg-sky-700 text-xs font-semibold text-white">
                            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="hidden min-w-0 text-left sm:block">
                            <p class="max-w-32 truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $roleLabel }}</p>
                        </div>
                    </div>
                </summary>
                <div class="absolute right-0 z-50 mt-3 w-60 max-w-[calc(100vw-1rem)] rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_28px_70px_-40px_rgba(15,23,42,0.34)]">
                    @if(Route::has('profile.edit'))
                        <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                            Mon profil
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mt-1 block w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                            Deconnexion
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
