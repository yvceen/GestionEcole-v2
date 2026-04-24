<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subjectText = $this->data['subject'] ?: 'Sans objet';
        $subject = sprintf('Message de contact — %s', $subjectText);

        return $this->subject($subject)
            ->view('emails.contact-message')
            ->with('data', $this->data);
    }
}
