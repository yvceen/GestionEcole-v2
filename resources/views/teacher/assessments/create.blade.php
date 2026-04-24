<x-teacher-layout title="Créer une évaluation">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Créer une évaluation</h1>
            <p class="text-sm text-slate-500 mt-1">اختار القسم والمادة وجدول التقييم</p>
        </div>
        <a href="{{ route('teacher.assessments.index') }}" class="text-sm font-semibold text-slate-700 hover:underline">← Retour</a>
    </div>

    @if($errors->any())
        <div class="mt-5 rounded-3xl border border-red-200 bg-red-50 p-4 text-red-900">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.assessments.store') }}"
          class="mt-6 rounded-[28px] border border-black/10 bg-white/80 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-semibold mb-1">Classe</label>
            <select name="classroom_id" class="w-full rounded-2xl border border-black/10 px-4 py-2.5" required>
                @foreach($classrooms as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Matière</label>
            <select name="subject_id" class="w-full rounded-2xl border border-black/10 px-4 py-2.5" required>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Titre</label>
            <input name="title" class="w-full rounded-2xl border border-black/10 px-4 py-2.5" required>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Date</label>
            <input type="date" name="date" class="w-full rounded-2xl border border-black/10 px-4 py-2.5" required>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Note maximale</label>
            <input type="number" name="max_score" value="20" class="w-full rounded-2xl border border-black/10 px-4 py-2.5" required>
        </div>

        <button class="rounded-2xl bg-black text-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-900">
            Enregistrer
        </button>
    </form>
</x-teacher-layout>
