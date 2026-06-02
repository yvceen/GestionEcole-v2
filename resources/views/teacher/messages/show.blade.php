@php
    $routePrefix = 'teacher.messages';
    $layoutComponent = 'teacher-layout';
    $canModerate = false;
    $canCompose = true;
    $replyHelpText = $replyRecipient?->role === 'parent'
        ? 'Votre réponse sera visible apres validation admin.'
        : 'La réponse sera envoyee directement au destinataire.';
@endphp

@include('partials.messages.show-shell')
