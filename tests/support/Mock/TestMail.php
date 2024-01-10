<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct(array $data, $view)
    {
        $this->data = $data;
        $this->view = $view;
    }

    public function build()
    {
        return $this
            ->view($this->view)
            ->with($this->data);
    }
}