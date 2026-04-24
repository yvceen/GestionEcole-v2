<x-admin-layout title="Niveaux">
    <h1 class="text-2xl font-semibold text-slate-900">Créer un niveau</h1>

    @if($errors->any())
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.levels.store') }}"
          class="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
        @csrf

        <div>
            <label class="block text-sm font-semibold mb-1">Nom</label>
            <input name="name" value="{{ old('name') }}"
                   class="w-full rounded-xl border border-slate-200 px-4 py-2" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Code (optionnel)</label>
                <input name="code" value="{{ old('code') }}"
                       class="w-full rounded-xl border border-slate-200 px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Ordre</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                       class="w-full rounded-xl border border-slate-200 px-4 py-2">
            </div>
        </div>

        <div class="flex items-center
