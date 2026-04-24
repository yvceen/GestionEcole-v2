<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $honeypot = trim((string) $request->input('website'));
        if ($honeypot !== '') {
            return back()->with('success', 'Message envoye.');
        }

        $ip = (string) $request->ip();
        $rateKey = 'contact-form:' . sha1($ip);
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $wait = max(1, (int) ceil($seconds / 60));

            return back()
                ->withErrors(['contact' => "Trop de tentatives. Reessayez dans {$wait} minute(s)."])
                ->withInput();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L email est obligatoire.',
            'email.email' => 'Veuillez saisir un email valide.',
            'subject.required' => 'L objet est obligatoire.',
            'message.required' => 'Le message est obligatoire.',
        ]);

        RateLimiter::hit($rateKey, $decaySeconds);

        $to = (string) env('CONTACT_TO', 'yassine@myedu.school');

        try {
            Mail::to($to)->send(new ContactMessageMail($data));

            return back()->with('success', 'Message envoye.');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['contact' => "Impossible d envoyer le message pour le moment. Merci de reessayer plus tard."])
                ->withInput();
        }
    }
}
