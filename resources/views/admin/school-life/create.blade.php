{{-- resources/views/admin/school-life/create.blade.php --}}
<x-admin-layout title="Nouvelle entree">
    <x-slot name="header">Nouvelle entree</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-slate-900">Nouvelle entree</h1>
            <a href="{{ route('admin.school-life.index') }}"
               class="rounded-2xl border border-black/10 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                Retour
            </a>
        </div>

        <form method="POST" action="{{ route('admin.school-life.store') }}" class="mt-6 space-y-4 rounded-[28px] border border-black/10 bg-white/80 backdrop-blur-xl p-6 shadow-sm">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Titre</label>
                <input name="title" value="{{ old('title') }}" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                @error('title')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Statut</label>
                <select name="status" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                    @foreach(['draft' => 'Brouillon', 'published' => 'Publie', 'archived' => 'Archive'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', 'draft') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Date</label>
                <input type="date" name="date" value="{{ old('date') }}" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm">
                @error('date')<div class="text-sm text-rose-600 mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="flex gap-2">
                <button class="rounded-2xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-black">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
