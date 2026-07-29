<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeInitiatedMail extends Mailable
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
            subject: 'Email Change Request Initiated – Rapid Rescue',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_change_initiated',
        );
    }
}
