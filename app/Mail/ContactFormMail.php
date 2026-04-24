<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $senderName;
    public $senderEmail;
    public $senderPhone;
    public $subject_text;
    public $message;
    public $ip;
    public $timestamp;

    public function __construct($name, $email, $phone, $subject, $message, $ip, $timestamp)
    {
        $this->senderName = $name;
        $this->senderEmail = $email;
        $this->senderPhone = $phone;
        $this->subject_text = $subject;
        $this->message = $message;
        $this->ip = $ip;
        $this->timestamp = $timestamp;
    }

    public function envelope()
    {
        return new \Illuminate\Mail\Envelope(
            subject: 'Nouveau message de contact — ' . $this->subject_text
        );
    }

    public function content()
    {
        return new \Illuminate\Mail\Content(
            view: 'emails.contact-form'
        );
    }

    public function attachments()
    {
        return [];
    }
}
