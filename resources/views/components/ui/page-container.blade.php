@props([
    'fluid' => false,
])

<div {{ $attributes->merge(['class' => "w-full overflow-x-hidden " . ($fluid ? '' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8')]) }}>
    {{ $slot }}
</div>
