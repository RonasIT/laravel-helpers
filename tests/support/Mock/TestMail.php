<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Mail\BaseMail;

class TestMail extends BaseMail
{
    public function __construct(array $viewData, $view, $subject = '')
    {
        parent::__construct($viewData, $view, $subject);

        $this->setAddress('noreply@mail.net', null, 'from');
    }
}