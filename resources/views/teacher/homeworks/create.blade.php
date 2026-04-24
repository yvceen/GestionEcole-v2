<x-teacher-layout title="Nouveau devoir">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Creer un devoir</h1>
    </div>

    <form method="POST" action="{{ route('teacher.homeworks.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-2xl border border-black/10 bg-white p-6">
        @csrf

        <div>
            <label class="block text-sm font-semibold mb-1">Classe</label>
            <select name="classroom_id" class="w-full rounded-xl border-black/10" required>
                <option value="">-- Choisir --</option>
                @foreach($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Matiere</label>
            <select name="subject_id" class="w-full rounded-xl border-black/10">
                <option value="">-- Choisir --</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Titre</label>
            <input name="title" value="{{ old('title') }}" class="w-full rounded-xl border-black/10" required />
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Description</label>
            <textarea name="description" rows="4" class="w-full rounded-xl border-black/10">{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Date limite</label>
            <input type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="w-full rounded-xl border-black/10" />
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1">Fichiers</label>
            <input type="file" name="files[]" multiple class="w-full rounded-xl border-black/10" />
        </div>

        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Enregistrer</button>
    </form>
</x-teacher-layout>
