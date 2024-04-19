<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Mail\BaseMail;

class TestMailWithAttachments extends BaseMail
{
    public function __construct(array $viewData, $subject, $view)
    {
        parent::__construct($viewData, $subject, $view);

        $this->setAddress('noreply@mail.net', null, 'from');
    }

    public function attachments(): array
    {
        return [
            new \stdClass(),
            new \stdClass(),
        ];
    }
}