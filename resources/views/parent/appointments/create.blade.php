<x-parent-layout title="Demander un rendez-vous">
    <div class="mx-auto max-w-3xl px-4 py-6 sm:px-6">
        <h1 class="text-2xl font-semibold text-slate-900">Demander un rendez-vous</h1>
        <p class="mt-1 text-sm text-slate-700">Envoyez une demande. L administration pourra confirmer, refuser ou replanifier depuis le module existant.</p>

        <form method="POST" action="{{ route('parent.appointments.store') }}"
              class="mt-6 space-y-4 rounded-[28px] border border-black/10 bg-white/80 p-6 shadow-sm">
            @csrf

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="ml-5 list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Titre</label>
                <input name="title" value="{{ old('title') }}"
                       placeholder="Ex: Suivi pedagogique"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Date et heure souhaitees</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
            </div>

            @if(($children ?? collect())->isNotEmpty())
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Enfant concerne</label>
                    <select name="student_id" class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
                        <option value="">Aucun enfant specifique</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected((int) old('student_id') === (int) $child->id)>
                                {{ $child->full_name }}{{ $child->classroom?->name ? ' - ' . $child->classroom->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Telephone (optionnel)</label>
                <input name="parent_phone" value="{{ old('parent_phone') }}"
                       placeholder="06 xx xx xx xx"
                       class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20">
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-700">Message / details</label>
                <textarea name="message" rows="4"
                          placeholder="Expliquez brievement votre demande..."
                          class="w-full rounded-2xl border border-black/10 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/20">{{ old('message') }}</textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button class="rounded-2xl bg-slate-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-black">
                    Envoyer
                </button>

                <a href="{{ route('parent.appointments.index') }}"
                   class="rounded-2xl border border-black/10 bg-white px-6 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-parent-layout>
