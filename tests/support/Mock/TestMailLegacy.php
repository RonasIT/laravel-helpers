<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class TestMailLegacy extends Mailable implements ShouldQueue
{
    public function __construct(array $viewData, $subject, $view)
    {
        $this->viewData = $viewData;
        $this->subject = $subject;
        $this->view = $view;
        $this->queue = 'mails';

        $this->setAddress('noreply@mail.net', null, 'from');
    }

    public function build()
    {
        return $this
            ->view($this->view)
            ->subject($this->subject)
            ->with($this->viewData)
            ->attach('/path/to/file', [
                'as' => 'name.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}