<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $firstName;

    public function __construct(string $code, string $firstName)
    {
        $this->code      = $code;
        $this->firstName = $firstName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your New Email Address – Rapid Rescue',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email_change_verification',
        );
    }
}
