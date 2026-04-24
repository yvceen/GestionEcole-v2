@props([
    'empty' => 'Aucune donnee disponible.',
    'colspan' => 1,
    'class' => '',
])

<div {{ $attributes->merge(['class' => "app-card overflow-hidden p-0 {$class}"]) }}>
    <div class="overflow-x-auto">
        <table class="app-table">
            @if(isset($head))
                <thead>{{ $head }}</thead>
            @endif
            <tbody>
                @if(trim((string) $slot) !== '')
                    {{ $slot }}
                @else
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-6 py-14 text-center text-sm text-slate-500">
                            {{ $empty }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
