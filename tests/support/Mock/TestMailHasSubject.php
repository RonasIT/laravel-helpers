<?php

namespace RonasIT\Support\Tests\Support\Mock;

class TestMailHasSubject extends TestMail
{
    public function __construct(array $viewData, $subject, $view)
    {
        parent::__construct($viewData, $subject, $view);

        $this->queue = 'different_queue';
    }

    public function hasSubject(string $subject): bool
    {
        return ($this->subject === $subject);
    }
}