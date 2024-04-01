<?php

namespace RonasIT\Support\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BaseMail extends Mailable implements ShouldQueue
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
    }
}
