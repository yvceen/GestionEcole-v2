<x-admin-layout title="Ajouter un élève">
    <x-students.header
        title="Ajouter un élève"
        subtitle="Création complète : informations, parent, compte élève et paramètres."
    >
        <x-ui.button :href="route('admin.students.index')" variant="ghost">
            Retour
        </x-ui.button>
    </x-students.header>

    <x-students.form
        mode="create"
        :action="route('admin.students.store')"
        method="POST"
        :classrooms="$classrooms"
        :parents="$parents"
        :routes="($routes ?? collect())"
        :vehicles="($vehicles ?? collect())"
    />
</x-admin-layout>
