@php
    $routePrefix = 'parent.messages';
    $layoutComponent = 'parent-layout';
    $canModerate = false;
    $canCompose = true;
    $replyHelpText = 'La reponse est ajoutee a la conversation et envoyee directement au destinataire.';
@endphp

@include('partials.messages.show-shell')
