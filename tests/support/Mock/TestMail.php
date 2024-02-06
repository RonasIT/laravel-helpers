<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function __construct(array $viewData, $view, $subject = '')
    {
        $this->viewData = $viewData;
        $this->view = $view;
        $this->subject = $subject;

        $this->queue = 'mails';
        $this->setAddress('noreply@mail.net', null, 'from');
    }
}