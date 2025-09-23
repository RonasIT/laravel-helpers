<?php

namespace RonasIT\Support\Tests\Support\Mock\Mails;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use RonasIT\Support\Mail\BaseMail;

class TestMailViewDataViaProperty extends BaseMail
{
    public function __construct(
        public string $name,
    ) {
        parent::__construct();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'noreply@mail.net',
            subject: 'Test Subject',
        );
    }

    public function content(): Content
    {
        return new Content('emails.test');
    }
}
