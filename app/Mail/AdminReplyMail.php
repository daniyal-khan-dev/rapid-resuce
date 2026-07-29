<?php

namespace App\Mail;

use App\Models\Admin\ContactReply;
use App\Models\User\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public ContactMessage $contactMessage;
    public ContactReply $contactReply;

    public function __construct(ContactMessage $contactMessage, ContactReply $contactReply)
    {
        $this->contactMessage = $contactMessage;
        $this->contactReply   = $contactReply;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rapid Rescue Support Replied to Your Message',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_reply',
        );
    }
}
