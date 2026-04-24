@php
    $role = $role ?? (string) auth()->user()?->role;
    $routePrefix = match ($role) {
        'school_life' => 'school-life',
        'admin', 'teacher', 'student', 'parent' => $role,
        default => 'parent',
    };
    $layoutComponent = match ($role) {
        'admin' => 'admin-layout',
        'teacher' => 'teacher-layout',
        'student' => 'student-layout',
        'parent' => 'parent-layout',
        'school_life' => 'school-life-layout',
        'director' => 'director-layout',
        'super_admin' => 'super-layout',
        default => 'app-shell',
    };
@endphp

<x-dynamic-component :component="$layoutComponent" title="Notifications">
    <div class="ui-scope space-y-6">
        <section class="app-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Notifications</h2>
                    <p class="text-sm text-slate-600">All your recent updates.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if(Route::has($routePrefix.'.notifications.push_test'))
                        <form method="POST" action="{{ route($routePrefix.'.notifications.push_test') }}">
                            @csrf
                            <button class="app-button-secondary" type="submit">Tester push Android</button>
                        </form>
                    @endif
                    @if(Route::has($routePrefix.'.notifications.read_all'))
                        <form method="POST" action="{{ route($routePrefix.'.notifications.read_all') }}">
                            @csrf
                            <button class="app-button-secondary">Tout marquer comme lu</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="app-card p-6">
            <div class="space-y-3">
                @forelse($notifications as $notification)
                    @php
                        $isRead = (bool) $notification->read_at;
                        $badge = $isRead ? 'bg-slate-100 text-slate-600' : 'bg-blue-100 text-blue-700';
                        $openRoute = Route::has($routePrefix.'.notifications.open')
                            ? route($routePrefix.'.notifications.open', $notification)
                            : '#';
                    @endphp
                    <a href="{{ $openRoute }}" class="block rounded-2xl border border-slate-200/80 bg-white p-4 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($notification->body, 160) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ optional($notification->created_at)->diffForHumans() }}</p>
                            </div>
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $badge }}">{{ $isRead ? 'Lu' : 'Nouveau' }}</span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">
                        No notifications yet
                    </div>
                @endforelse
            </div>

            @if(method_exists($notifications, 'links'))
                <div class="mt-5">
                    {{ $notifications->links() }}
                </div>
            @endif
        </section>
    </div>
</x-dynamic-component>
