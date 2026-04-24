@php
    $classroomsCollection = collect($classrooms ?? []);
    $parentsCollection = collect($parents ?? []);
    $teachersCollection = collect($teachers ?? []);

    $tabs = [
        [
            'key' => 'classes',
            'label' => 'Classes',
            'description' => 'Envoyez un message à toute une classe.',
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
            'description' => 'Choisissez un ou plusieurs parents.',
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
            'description' => 'Enseignants, administrateurs et autres intervenants.',
            'items' => $teachersCollection->map(function ($teacher) {
                $label = $teacher->name ?? 'Personne #' . $teacher->id;
                $meta = $teacher->role ?? '';
                return [
                    'id' => $teacher->id,
                    'label' => $label,
                    'meta' => $meta,
                ];
            })->values()->all(),
            'multi' => true,
            'field' => 'teacher_ids[]',
            'initialSelected' => old('teacher_ids', []),
        ],
    ];
@endphp

<x-admin-layout title="Nouveau message">
    <x-messaging-composer
        action="{{ route('admin.messages.store') }}"
        back-url="{{ route('admin.messages.index') }}"
        title="Nouveau message"
        subtitle="Envoyez une information à une classe, à plusieurs parents ou à un membre du personnel."
        :tabs="$tabs"
    />
</x-admin-layout>
