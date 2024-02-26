<?php

namespace RonasIT\Support\Tests\Support\Mock;

class TestMailHasSubject extends TestMail
{
    public function hasSubject(string $subject): bool
    {
        return ($this->subject === $subject);
    }
}