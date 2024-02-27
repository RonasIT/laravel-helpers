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

    public function __construct(array $viewData, $view, $subject)
    {
        $this->viewData = $viewData;
        $this->subject = $subject;
        $this->view = $view;
        $this->onQueue('mails');
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function build()
    {
        return $this
            ->view($this->view, $this->viewData)
            ->subject($this->subject)
            ->onQueue('mails');
    }
}
