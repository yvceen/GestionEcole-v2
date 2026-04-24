@php
    $classroomsCollection = collect($classrooms ?? []);
    $parentsCollection = collect($parents ?? []);

    $tabs = [
        [
            'key' => 'classes',
            'label' => 'Classes',
            'description' => 'Partagez un message à un groupe entier.',
            'items' => $classroomsCollection->map(function ($class) {
                $label = $class->name ?? $class->label ?? 'Classe #' . $class->id;
                $meta = $class->level ?? $class->grade ?? '';
                return [
                    'id' => $class->id,
                    'label' => $label,
                    'meta' => $meta,
                ];
            })->values()->all(),
            'multi' => false,
            'field' => 'classroom_id',
            'initialSelected' => collect([old('classroom_id')])->filter(fn ($value) => !is_null($value) && $value !== '')->values()->all(),
        ],
        [
            'key' => 'parents',
            'label' => 'Parents',
            'description' => 'Sélectionnez des parents ou plusieurs.',
            'items' => $parentsCollection->map(function ($parent) {
                $label = $parent->name ?? 'Parent #' . $parent->id;
                $meta = $parent->email ?? ($parent->phone ?? '');
                return [
                    'id' => $parent->id,
                    'label' => $label,
                    'meta' => $meta,
                ];
            })->values()->all(),
            'multi' => true,
            'field' => 'parent_ids[]',
            'initialSelected' => old('parent_ids', []),
        ],
        [
            'key' => 'staff',
            'label' => 'Personnel',
            'description' => 'Admins et direction (lecture uniquement).',
            'items' => [],
            'multi' => true,
            'field' => null,
            'initialSelected' => [],
        ],
    ];
@endphp

<x-teacher-layout title="Nouveau message (Enseignant)">
    <x-messaging-composer
        action="{{ route('teacher.messages.store') }}"
        back-url="{{ route('teacher.messages.index') }}"
        title="Nouveau message"
        subtitle="Les messages aux parents passent par validation admin."
        :tabs="$tabs"
    />
</x-teacher-layout>
