@php
    $doc = $documentModel;
@endphp

<x-ui.input label="Titre" name="title" :value="old('title', $doc?->title)" required />

<div>
    <label class="app-label" for="summary">Resume</label>
    <textarea id="summary" name="summary" rows="3" class="app-input">{{ old('summary', $doc?->summary) }}</textarea>
</div>

<x-ui.select label="Categorie" name="category">
    @foreach($categories as $value)
        <option value="{{ $value }}" @selected(old('category', $doc?->category ?? 'administratif') === $value)>{{ ucfirst($value) }}</option>
    @endforeach
</x-ui.select>

<x-ui.select label="Audience" name="audience_scope">
    @foreach($audiences as $value)
        <option value="{{ $value }}" @selected(old('audience_scope', $doc?->audience_scope ?? 'school') === $value)>{{ ucfirst($value) }}</option>
    @endforeach
</x-ui.select>

<x-ui.select label="Role cible (si audience = role)" name="role">
    <option value="">Choisir un role</option>
    @foreach($roles as $role)
        <option value="{{ $role }}" @selected(old('role', $doc?->role) === $role)>{{ \App\Models\User::labelForRole($role) }}</option>
    @endforeach
</x-ui.select>

<x-ui.select label="Classe cible (si audience = classroom)" name="classroom_id">
    <option value="">Choisir une classe</option>
    @foreach($classrooms as $classroom)
        <option value="{{ $classroom->id }}" @selected((int) old('classroom_id', $doc?->classroom_id) === (int) $classroom->id)>{{ $classroom->name }}</option>
    @endforeach
</x-ui.select>

<div>
    <label class="app-label" for="document">Fichier</label>
    <input id="document" type="file" name="document" class="app-input">
    @if($doc?->file_url)
        <p class="mt-2 text-xs text-slate-500">Fichier actuel :
            <a href="{{ $doc->file_url }}" target="_blank" rel="noopener" class="font-semibold text-sky-700 hover:underline">ouvrir</a>
        </p>
    @endif
</div>

<label class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $doc?->is_active ?? true))>
    <span class="text-sm text-slate-700">Document visible</span>
</label>
