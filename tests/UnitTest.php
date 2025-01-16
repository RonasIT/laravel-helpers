<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Auth;

class UnitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::shouldReceive('id')->andReturn(1);
    }
}