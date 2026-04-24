@props(['size' => 40])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <x-school-logo :size="$size" />

    <div class="leading-tight">
        <div class="text-sm font-semibold text-slate-900">
            {{ config('app.name', 'My Edu') }}
        </div>
        <div class="text-xs text-slate-500">
            Plateforme scolaire
        </div>
    </div>
</div>
