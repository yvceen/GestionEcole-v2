@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'class' => '',
])

@php($fieldId = $attributes->get('id') ?? $name)

<div class="app-field">
    @if($label)
        <label @if($fieldId) for="{{ $fieldId }}" @endif class="app-label">
            {{ $label }}
        </label>
    @endif
    <select
        @if($fieldId) id="{{ $fieldId }}" @endif
        @if($name) name="{{ $name }}" @endif
        {{ $attributes->except('id')->merge(['class' => "app-input {$class}"]) }}
    >
        {{ $slot }}
    </select>
    @if($hint)
        <p class="app-hint">{{ $hint }}</p>
    @endif
</div>
