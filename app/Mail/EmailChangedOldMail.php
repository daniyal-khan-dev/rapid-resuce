<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangedOldMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $firstName;
    public string $newEmail;

    public function __construct(string $firstName, string $newEmail)
    {
        $this->firstName = $firstName;
        $this->newEmail  = $newEmail;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Account Email Has Been Changed – Rapid Rescue',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_changed_old',
        );
    }
}
