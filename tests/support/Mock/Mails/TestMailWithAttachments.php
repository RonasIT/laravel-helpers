<?php

namespace RonasIT\Support\Tests\Support\Mock\Mails;

class TestMailWithAttachments extends TestMail
{
    public function __construct(array $viewData)
    {
        parent::__construct($viewData);

        $this->setAddress('noreply@mail.net', null, 'from');
    }

    public function attachments(): array
    {
        return [
            ['file' => 'attachment1', 'options' => []],
            ['file' => new \stdClass(), 'options' => ['some_options']],
        ];
    }

    public function assertHasAttachment($file, array $options = []): bool
    {
        return true;
    }
}
