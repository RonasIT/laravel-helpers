<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct(array $data, $view, $subject = '')
    {
        $this->data = $data;
        $this->view = $view;
        $this->subject = $subject;

        $this->queue = 'mails';
        $this->setAddress('noreply@mail.net', null, 'from');
    }
}