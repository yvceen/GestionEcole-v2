<x-parent-layout title="Devoir">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-black/5 bg-white/70 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ $homework->classroom?->name ?? '—' }} • {{ $homework->classroom?->level?->name ?? '—' }}
            </div>

            <h1 class="mt-4 text-2xl font-semibold tracking-tight text-slate-900">
                {{ $homework->title }}
            </h1>

            <div class="mt-2 text-sm text-slate-600">
                Deadline: <span class="font-semibold text-slate-900">{{ $homework->due_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('parent.homeworks.index') }}"
               class="inline-flex items-center justify-center rounded-2xl border border-black/5 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white transition">
                ← رجوع
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Devoir details --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-[30px] border border-black/5 bg-white/70 p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)]">
                <div class="text-sm font-semibold text-slate-900">Description</div>
                <p class="mt-2 text-sm text-slate-600 whitespace-pre-line">
                    {{ $homework->description ?? '—' }}
                </p>
            </div>

            <div class="rounded-[30px] border border-black/5 bg-white/70 p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)]">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold text-slate-900">Fichiers du devoir</div>
                    <div class="text-xs text-slate-500">{{ $homework->files->count() }} fichier(s)</div>
                </div>

                @if($homework->files->count())
                    <div class="mt-4 space-y-2">
                        @foreach($homework->files as $f)
                            <a href="{{ asset('storage/'.$f->path) }}" target="_blank"
                               class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/60 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-white transition">
                                <span class="truncate">{{ $f->original_name }}</span>
                                <span class="text-xs text-slate-500">Télécharger</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 text-sm text-slate-600">Aucun fichier.</div>
                @endif
            </div>

            <div class="rounded-[30px] border border-black/5 bg-white/70 p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)]">
                <div class="text-sm font-semibold text-slate-900">Mes submissions</div>

                @if($submissions->isEmpty())
                    <div class="mt-3 text-sm text-slate-600">Aucune submission envoyée pour le moment.</div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach($submissions as $s)
                            <div class="rounded-3xl border border-slate-200/70 bg-white/60 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ $s->student?->full_name ?? 'Élève' }}
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            Envoyé: {{ $s->submitted_at?->format('d/m/Y H:i') ?? $s->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>

                                @if($s->description)
                                    <p class="mt-2 text-sm text-slate-600 whitespace-pre-line">{{ $s->description }}</p>
                                @endif

                                @if($s->files->count())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach($s->files as $f)
                                            <a href="{{ asset('storage/'.$f->path) }}" target="_blank"
                                               class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-800 hover:bg-slate-50 transition">
                                                {{ $f->original_name }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Submission form --}}
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('parent.homeworks.submit', $homework) }}" enctype="multipart/form-data"
                  class="rounded-[30px] border border-black/5 bg-white/70 p-6 shadow-[0_22px_60px_-40px_rgba(0,0,0,.50)]">
                @csrf

                <div class="text-sm font-semibold text-slate-900">Envoyer une submission</div>
                <p class="mt-1 text-xs text-slate-500">اختار ولدك، كتب description، وحطّ fichiers/images.</p>

                <div class="mt-4">
                    <label class="text-xs font-semibold text-slate-700">Élève</label>
                    <select name="student_id" class="mt-1 w-full rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 text-sm">
                        @foreach($children as $c)
                            <option value="{{ $c->id }}">{{ $c->full_name }} — {{ $c->classroom?->name ?? '—' }}</option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <div class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-xs font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="5"
                              class="mt-1 w-full rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 text-sm"
                              placeholder="ملاحظات / شرح..."></textarea>
                    @error('description')
                        <div class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="text-xs font-semibold text-slate-700">Fichiers (max 10)</label>
                    <input type="file" name="files[]" multiple
                           class="mt-1 w-full rounded-2xl border border-slate-200 bg-white/70 px-3 py-2 text-sm" />
                    @error('files')
                        <div class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</div>
                    @enderror
                    @error('files.*')
                        <div class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit"
                        class="mt-5 w-full rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black transition">
                    Envoyer ✅
                </button>
            </form>
        </div>
    </div>
</x-parent-layout>
