<?php

namespace RonasIT\Support\Tests\Support\Mock;

class TestMailManyFromWithName extends TestMail
{
    public function __construct(array $viewData)
    {
        parent::__construct($viewData);

        $this->from('noreply@mail.net', 'Some sender');
        $this->from('noreply-second@mail.net', 'Some sender second case');
        $this->from('noreply-withoutsender@mail.net');
        $this->from('noreply-withoutsender-second@mail.net');
    }
}
