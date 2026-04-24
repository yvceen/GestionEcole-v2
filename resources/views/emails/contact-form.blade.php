<x-mail::message>
# Nouveau message de contact

**De:** {{ $senderName }}
**Email:** {{ $senderEmail }}
**Téléphone:** {{ $senderPhone }}
**Sujet:** {{ $subject_text }}

---

## Message

{{ $message }}

---

**Date:** {{ $timestamp->format('d/m/Y H:i:s') }}
**IP source:** {{ $ip }}

</x-mail::message>
