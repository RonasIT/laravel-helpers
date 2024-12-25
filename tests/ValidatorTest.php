<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;

class ValidatorTest extends HelpersTestCase
{
    use SqlMockTrait;

    public function setUp(): void
    {
        parent::setUp();

        Auth::shouldReceive('id')->andReturn(1);
    }

    public function testUniqueExceptOfAuthorizedUserPass()
    {
        $this->mockExistsUsersExceptAuthorized();

        $validator = Validator::make(
            ['email' => 'mail@mail.com'],
            ['email' => 'unique_except_of_authorized_user'],
        );

        $this->assertTrue($validator->passes());
    }

    public function testUniqueExceptOfAuthorizedUserPassWithArray()
    {
        $this->mockExistsUsersExceptAuthorizedByArray(false, 'clients');

        $validator = Validator::make(
            ['email' => [['mail@mail.com'], ['mail@mail.net']]],
            ['email' => 'unique_except_of_authorized_user:clients'],
        );

        $this->assertTrue($validator->passes());
    }

    public function testUniqueExceptOfAuthorizedUserPassWithDifferentKeyField()
    {
        $this->mockExistsUsersExceptAuthorizedByArray(false, 'clients', 'user_id');

        $validator = Validator::make(
            ['email' => [['mail@mail.com'], ['mail@mail.net']]],
            ['email' => 'unique_except_of_authorized_user:clients,user_id'],
        );

        $this->assertTrue($validator->passes());
    }

    public function testUniqueExceptOfAuthorizedUserFail()
    {
        $this->mockExistsUsersExceptAuthorizedByArray(true);

        $validator = Validator::make(
            ['email' => ['mail@mail.com', 'mail@mail.net']],
            ['email' => 'unique_except_of_authorized_user'],
        );

        $this->assertTrue($validator->fails());
    }

    public function testListExists()
    {
        $this->mockListExists([1, 2, 3]);

        $validator = Validator::make(
            ['ids' => [1, 2, 3]],
            ['ids' => 'list_exists:clients,user_id'],
        );

        $this->assertTrue($validator->passes());
    }

    public function testListExistsIfDuplicateValues()
    {
        $this->mockListExists([1, 2, 3]);

        $validator = Validator::make([
            'ids' => [1, 2, 3, 3],
        ], [
            'ids' => 'list_exists:clients,user_id',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function testListExistsByArray()
    {
        $this->mockListExists([1, 2, 3]);

        $validator = Validator::make(
            [
                'ids' => [
                    [
                        'id' => 1,
                        'name' => 'name1',
                    ],
                    [
                        'id' => 2,
                        'name' => 'name2',
                    ],
                    [
                        'id' => 3,
                        'name' => 'name3',
                    ],
                ],
            ],
            [
                'ids' => 'list_exists:clients,user_id,id',
            ],
        );

        $this->assertTrue($validator->passes());
    }

    public function testListExistsFailedValidation()
    {
        $this->mockListExists([1, 2]);

        $validator = Validator::make(
            ['ids' => [1, 2, 3]],
            ['ids' => 'list_exists:clients,user_id'],
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('validation.list_exists', $validator->errors()->first('ids'));
    }

    public function testListExistsWithoutArgs()
    {
        $this->expectException(InvalidValidationRuleUsageException::class);
        $this->expectExceptionMessage('You must add at least 1 parameter.');

        $validator = Validator::make(
            ['ids' => [1, 2, 3]],
            ['ids' => 'list_exists'],
        );

        $this->assertTrue($validator->fails());
    }

    public function testListExistsIncorrectParameters()
    {
        $validator = Validator::make(
            [
                'ids' => [
                    [
                        'id' => 1,
                        'name' => 'name1',
                    ],
                    [
                        'id' => 2,
                        'name' => 'name2',
                    ],
                    [
                        'id' => 3,
                        'name' => 'name3',
                    ],
                ],
            ],
            [
                'ids' => 'list_exists:clients,user_id',
            ],
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('The third argument should be filled for collections input.', $validator->errors()->first('ids'));
    }
}
