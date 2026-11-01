<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ContactMessage extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $renderedHtml,
        public readonly string $replyAddress,
    ) {
    }

    public function envelope(): Envelope {
        return new Envelope(
            subject: 'HuntRecipes Contact',
            replyTo: [new Address($this->replyAddress)],
        );
    }

    public function content(): Content {
        return new Content(htmlString: $this->renderedHtml);
    }
}
