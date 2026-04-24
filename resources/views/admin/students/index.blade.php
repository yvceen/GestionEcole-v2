<x-admin-layout title="Gestion des élèves">
    <x-page-header
        title="Gestion des élèves"
        subtitle="Suivi des inscriptions, des classes et des informations parentales."
        eyebrow="Élèves"
    >
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
            <form method="GET" action="{{ route('admin.students.index') }}" class="relative w-full sm:w-80">
                @if($classroomId)
                    <input type="hidden" name="classroom" value="{{ $classroomId }}">
                @endif
                <input type="hidden" name="status" value="{{ $status ?? 'active' }}">

                <x-ui.input
                    id="studentsSearch"
                    name="q"
                    :value="$q ?? ''"
                    placeholder="Rechercher par nom, email ou téléphone..."
                    autocomplete="off"
                    class="pr-10"
                />

                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                    </svg>
                </button>

                <div id="studentsSuggestBox" class="absolute z-20 mt-2 hidden w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"></div>
            </form>

            <x-ui.button :href="route('admin.students.create')" variant="primary" size="lg">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                Nouvel élève
            </x-ui.button>
        </div>
    </x-page-header>

    <x-students.table
        :students="$students"
        :classrooms="$classrooms"
        :classroom-id="$classroomId"
        :q="$q"
        :status="$status"
    />
</x-admin-layout>
