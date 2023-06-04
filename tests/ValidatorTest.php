<?php

namespace RonasIT\Support\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use RonasIT\Support\Tests\Support\Traits\MockTrait;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ValidatorTest extends HelpersTestCase
{
    use MockTrait, SqlMockTrait;

    public function setUp(): void
    {
        parent::setUp();

        Auth::shouldReceive('id')
            ->andReturn(1);
    }

    public function testUniqueExceptOfAuthorizedUserPass()
    {
        $this->mockGetCountOfUsersExceptAuthorized(0);

        $validator = Validator::make(
            ['email' => 'mail@mail.com'],
            ['email' => 'unique_except_of_authorized_user']
        );

        $this->assertTrue($validator->passes());
    }

    public function testUniqueExceptOfAuthorizedUserFail()
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $validator = Validator::make(
            ['email' => ['mail@mail.com', 'mail@mail.net']],
            ['email' => 'unique_except_of_authorized_user']
        );

        $this->assertTrue($validator->fails());
        $this->expectExceptionMessage('The email must be a string.');
    }
}