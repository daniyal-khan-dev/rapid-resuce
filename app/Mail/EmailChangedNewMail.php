<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangedNewMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $firstName;

    public function __construct(string $firstName)
    {
        $this->firstName = $firstName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Email Address Has Been Updated – Rapid Rescue',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_changed_new',
        );
    }
}
