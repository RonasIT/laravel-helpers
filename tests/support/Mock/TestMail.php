<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use RonasIT\Support\Mail\BaseMail;

class TestMail extends BaseMail
{
    public function __construct(
        public $viewData
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
        return new Content(
            view: 'emails.test',
            with: $this->viewData,
        );
    }
}
