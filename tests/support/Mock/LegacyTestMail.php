<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LegacyTestMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public function __construct(array $viewData, $subject, $view)
    {
        $this->viewData = $viewData;
        $this->subject = $subject;
        $this->view = $view;
        $this->queue = 'mails';

        $this->from('noreply@mail.net');
    }
}
