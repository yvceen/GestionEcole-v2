@php
    $routePrefix = 'teacher.messages';
    $layoutComponent = 'teacher-layout';
    $canModerate = false;
    $canCompose = true;
    $replyHelpText = $replyRecipient?->role === 'parent'
        ? 'Votre reponse sera visible apres validation admin.'
        : 'La reponse sera envoyee directement au destinataire.';
@endphp

@include('partials.messages.show-shell')
