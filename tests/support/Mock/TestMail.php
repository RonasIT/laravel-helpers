<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct(array $data, $view, $subject = '', $from = [])
    {
        $this->data = $data;
        $this->view = $view;
        $this->subject = $subject;
        $this->from = $from;
    }

    public function build()
    {
        return $this
            ->view($this->view)
            ->with($this->data)
            ->onQueue('default');
    }
}