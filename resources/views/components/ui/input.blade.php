@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
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
    <input
        @if($fieldId) id="{{ $fieldId }}" @endif
        @if($name) name="{{ $name }}" @endif
        type="{{ $type }}"
        value="{{ $value }}"
        @if($required) required @endif
        {{ $attributes->except('id')->merge(['class' => "app-input {$class}"]) }}
    >
    @if($hint)
        <p class="app-hint">{{ $hint }}</p>
    @endif
</div>
