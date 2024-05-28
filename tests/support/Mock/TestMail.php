<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Mail\BaseMail;

class TestMail extends BaseMail
{
    public function __construct(array $viewData, $subject, $view)
    {
        parent::__construct($viewData, $subject, $view);

        $this->from('noreply@mail.net');
    }
}
