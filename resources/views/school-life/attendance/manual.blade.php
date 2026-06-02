<x-school-life-layout title="Saisie manuelle des présences" subtitle="Conservez le scan QR et ajoutez une saisie manuelle rapide pour corriger ou enregistrér un appel.">
    @include('partials.attendance.manual-register', [
        'routePrefix' => 'school-life.attendance.manual',
        'scanRoute' => route('school-life.qr-scan.index'),
        'manualRoute' => route('school-life.attendance.manual'),
        'storeRoute' => route('school-life.attendance.manual.store'),
        'historyRouteName' => 'school-life.attendance.manual',
        'pageTitle' => 'Registre manuel',
        'pageSubtitle' => "Saisissez ou corrigez les présences depuis la vie scolaire, tout en conservant le scan des cartes.",
        'modeBadge' => 'Saisie manuelle vie scolaire',
        'helperCopy' => "Les enregistréments existants pour la même date et le même Élève seront mis a jour. Les parents verront automatiquement l etat final enregistré.",
        'saveLabel' => !empty($attendanceByStudentId) ? 'Mettre a jour le registre' : 'Enregistrer le registre',
    ])
</x-school-life-layout>
