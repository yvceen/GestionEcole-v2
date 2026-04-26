@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $user = Auth::user();

    $links = [
        ['label' => 'Tableau de bord', 'route' => 'dashboard'],
    ];

    if ($user) {
        $byRole = match ($user->role) {
            'admin' => [
                ['label' => 'Eleves', 'route' => 'admin.students.index'],
                ['label' => 'Utilisateurs', 'route' => 'admin.users.index'],
                ['label' => 'Finance', 'route' => 'admin.finance.index'],
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
            ],
            'parent' => [
                ['label' => 'Enfants', 'route' => 'parent.children.index'],
                ['label' => 'Cours', 'route' => 'parent.courses.index'],
                ['label' => 'Devoirs', 'route' => 'parent.homeworks.index'],
            ],
            'student' => [
                ['label' => 'Cours', 'route' => 'student.courses.index'],
                ['label' => 'Devoirs', 'route' => 'student.homeworks.index'],
            ],
            default => [],
        };

        foreach ($byRole as $item) {
            if (Route::has($item['route'])) {
                $links[] = $item;
            }
        }
    }
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-white/20 bg-white/10 backdrop-blur-xl shadow-lg">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <x-application-logo class="block h-9 w-auto fill-current text-slate-900" />
                    <span class="text-sm font-semibold tracking-wide text-slate-900">
                        {{ config('app.name', 'My-Edu') }}
                    </span>
                </a>

                <div class="hidden sm:flex items-center gap-2 flex-wrap">
                    @foreach($links as $item)
                        <a href="{{ route($item['route']) }}"
                           class="group inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-all duration-200 {{ request()->routeIs($item['route']) || request()->routeIs(Str::beforeLast($item['route'], '.') . '.*') ? 'bg-white/20 text-slate-900 shadow-[0_12px_30px_-18px_rgba(59,130,246,0.45)] ring-1 ring-white/30' : 'text-slate-700 hover:text-slate-900 hover:bg-white/10 hover:scale-[1.03]' }}">
                            <svg class="h-5 w-5 text-slate-600 group-hover:text-slate-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 7h16M4 17h10" />
                            </svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-white/20 hover:text-slate-900 hover:scale-[1.02]">
                            <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" />
                            </svg>
                            <div>{{ Auth::user()->name }}</div>
                            <svg class="h-4 w-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Deconnexion
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-xl p-2 text-slate-700 hover:text-slate-900 hover:bg-white/10 transition duration-200">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-white/20 bg-white/10 backdrop-blur-xl">
        <div class="pt-2 pb-3 space-y-1 px-3">
            @foreach($links as $item)
                <a href="{{ route($item['route']) }}"
                   class="group flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition-all duration-200 {{ request()->routeIs($item['route']) || request()->routeIs(Str::beforeLast($item['route'], '.') . '.*') ? 'bg-white/20 text-slate-900 ring-1 ring-white/30' : 'text-slate-700 hover:text-slate-900 hover:bg-white/10' }}">
                    <svg class="h-5 w-5 text-slate-600 group-hover:text-slate-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 7h16M4 17h10" />
                    </svg>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="border-t border-white/20 px-4 py-4">
            <div class="font-medium text-base text-slate-900">{{ Auth::user()->name }}</div>
            <div class="font-medium text-sm text-slate-600">{{ Auth::user()->email }}</div>
            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-white/10 transition">
                    <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zM4 20a8 8 0 0116 0" />
                    </svg>
                    <span>{{ __('Profile') }}</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 hover:text-slate-900 hover:bg-white/10 transition"
                       onclick="event.preventDefault(); this.closest('form').submit();">
                        <svg class="h-5 w-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l4-4m-4 4l4 4M21 16v-1a2 2 0 00-2-2h-4m6 0V8a2 2 0 00-2-2h-4" />
                        </svg>
                        <span>{{ __('Log Out') }}</span>
                    </a>
                </form>
            </div>
        </div>
    </div>
</nav>
