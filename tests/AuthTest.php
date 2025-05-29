<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\Models\MockAuthUser;
use RonasIT\Support\Testing\TestCase as PackageTestCase;

class AuthTest extends TestCase
{
    public function testActingAs()
    {
        $mockedUser = new MockAuthUser();

        $mock = $this
            ->getMockBuilder(PackageTestCase::class)
            ->onlyMethods(['be'])
            ->setConstructorArgs(['name'])
            ->getMock();

        $mock->expects($this->once())
            ->method('be')
            ->with($this->callback(function ($cloneMockedUser) use ($mockedUser) {
                $this->assertNotSame($mockedUser, $cloneMockedUser);

                $this->assertEquals($mockedUser, $cloneMockedUser);

                return true;
            }))
            ->willReturn($mock);

        $mock->actingAs($mockedUser);
    }
}