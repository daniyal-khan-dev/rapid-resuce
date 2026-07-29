<?php

namespace App\Mail;

use App\Models\EmergencyRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmergencyRequestConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public EmergencyRequest $emergencyRequest;

    public function __construct(EmergencyRequest $emergencyRequest)
    {
        $this->emergencyRequest = $emergencyRequest;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Emergency Request Received — ' . $this->emergencyRequest->rreb_id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.emergency_request_confirmation',
        );
    }
}
