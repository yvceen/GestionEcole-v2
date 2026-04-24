@props([
    'label' => null,
    'name' => null,
    'rows' => 4,
    'hint' => null,
    'class' => '',
])

<div class="app-field">
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="app-label">
            {{ $label }}
        </label>
    @endif
    <textarea
        @if($name) id="{{ $name }}" name="{{ $name }}" @endif
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => "app-input {$class}"]) }}
    >{{ $slot }}</textarea>
    @if($hint)
        <p class="app-hint">{{ $hint }}</p>
    @endif
</div>
