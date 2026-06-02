<x-admin-layout title="Ajouter un Élève">
    <x-students.header
        title="Ajouter un Élève"
        subtitle="Création complete : informations, parent, compte Élève et parametres."
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
