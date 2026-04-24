<x-teacher-layout
    title="Registre d'appel"
    subtitle="Saisissez les presences, absences et retards par classe et par date, puis retrouvez vos derniers appels en un coup d'oeil."
>
    @include('partials.attendance.manual-register', [
        'routePrefix' => 'teacher.attendance',
        'scanRoute' => route('attendance.scan.page'),
        'manualRoute' => route('teacher.attendance.index'),
        'storeRoute' => route('teacher.attendance.store'),
        'historyRouteName' => 'teacher.attendance.index',
        'pageTitle' => "Registre d'appel",
        'pageSubtitle' => "Saisissez les presences, absences et retards par classe et par date, puis retrouvez vos derniers appels en un coup d'oeil.",
        'modeBadge' => 'Saisie manuelle enseignant',
        'helperCopy' => "Si un appel existe deja pour cette classe et cette date, la saisie est rechargee et sera mise a jour sans creer de doublons.",
        'saveLabel' => !empty($attendanceByStudentId) ? 'Mettre a jour le registre' : 'Enregistrer le registre',
    ])
</x-teacher-layout>
