<x-school-life-layout title="Saisie manuelle des presences" subtitle="Conservez le scan QR et ajoutez un mode de saisie manuelle rapide pour corriger ou enregistrer un appel.">
    @include('partials.attendance.manual-register', [
        'routePrefix' => 'school-life.attendance.manual',
        'scanRoute' => route('school-life.qr-scan.index'),
        'manualRoute' => route('school-life.attendance.manual'),
        'storeRoute' => route('school-life.attendance.manual.store'),
        'historyRouteName' => 'school-life.attendance.manual',
        'pageTitle' => 'Registre manuel',
        'pageSubtitle' => "Saisissez ou corrigez les presences depuis la vie scolaire, sans supprimer le flux QR.",
        'modeBadge' => 'Saisie manuelle vie scolaire',
        'helperCopy' => "Les enregistrements existants pour la meme date et le meme eleve seront mis a jour. Les parents verront automatiquement l etat final enregistre.",
        'saveLabel' => !empty($attendanceByStudentId) ? 'Mettre a jour le registre' : 'Enregistrer le registre',
    ])
</x-school-life-layout>
