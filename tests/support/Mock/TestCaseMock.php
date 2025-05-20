<?php

namespace RonasIT\Support\Tests\Support\Mock;

use RonasIT\Support\Testing\TestCase;

class TestCaseMock extends TestCase
{
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    public function setUpMock($app): self
    {
        $this->app = $app;

        parent::setUp();

        return $this;
    }
}