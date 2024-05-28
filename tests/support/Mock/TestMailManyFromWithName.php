<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Mail\BaseMail;

class TestMailManyFromWithName extends BaseMail
{
    public function __construct(array $viewData, $subject, $view)
    {
        parent::__construct($viewData, $subject, $view);

        $this->from('noreply@mail.net', 'Some sender');
        $this->from('noreply-second@mail.net', 'Some sender second case');
        $this->from('noreply-withoutsender@mail.net');
        $this->from('noreply-withoutsender-second@mail.net');
    }
}
