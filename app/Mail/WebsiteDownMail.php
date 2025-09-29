<?php

namespace App\Mail;

use App\Models\Website;
use App\Models\WebsiteCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class WebsiteDownMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Website $website,
        public WebsiteCheck $check
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('do-not-reply@example.com', 'Uptime Monitor'),
            subject: "{$this->website->url} is down!",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.website-down',
            with: [
                'website' => $this->website,
                'check' => $this->check,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
