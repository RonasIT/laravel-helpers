<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Enums\PostgresDatabaseTypeEnum;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;
use RonasIT\Support\Rules\DbTypeRangeRule;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;
use RonasIT\Support\Traits\TestingTrait;

class ValidatorTest extends TestCase
{
    use SqlMockTrait;
    use TestingTrait;

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
        $this->assertEquals('Some of the passed ids are not exists.', $validator->errors()->first('ids'));
    }

    public function testListExistsIncorrectFieldType()
    {
        $validator = Validator::make(
            ['ids' => 1],
            ['ids' => 'list_exists:clients,user_id'],
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('The ids field must be an array.', $validator->errors()->first('ids'));
    }

    public function testListExistsWithoutArgs()
    {
        $this->expectException(InvalidValidationRuleUsageException::class);
        $this->expectExceptionMessage('list_exists: At least 1 parameter must be added when checking the ids field in the request.');

        $validator = Validator::make(
            ['ids' => [1, 2, 3]],
            ['ids' => 'list_exists'],
        );

        $this->assertTrue($validator->fails());
    }

    public function testListExistsIncorrectParameters()
    {
        $this->expectException(InvalidValidationRuleUsageException::class);
        $this->expectExceptionMessage('The third parameter should be filled when checking the ids field if we are using a collection in request.');

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
    }

    public static function provideDbTypeRangePasses(): array
    {
        return [
            'integer min' => [
                'value' => -2147483648,
                'type' => 'integer',
            ],
            'integer max' => [
                'value' => 2147483647,
                'type' => 'integer',
            ],
            'smallint min' => [
                'value' => -32768,
                'type' => 'smallint',
            ],
            'smallint max' => [
                'value' => 32767,
                'type' => 'smallint',
            ],
            'bigint min' => [
                'value' => -9223372036854775808,
                'type' => 'bigint',
            ],
            'bigint max' => [
                'value' => 9223372036854775807,
                'type' => 'bigint',
            ],
            'serial min' => [
                'value' => 1,
                'type' => 'serial',
            ],
            'serial max' => [
                'value' => 2147483647,
                'type' => 'serial',
            ],
            'varchar min' => [
                'value' => '',
                'type' => 'varchar',
            ],
            'varchar max' => [
                'value' => str_repeat('a', 255),
                'type' => 'varchar',
            ],
            'text min' => [
                'value' => '',
                'type' => 'text',
            ],
        ];
    }

    #[DataProvider('provideDbTypeRangePasses')]
    public function testDbTypeRangePasses(mixed $value, string $type): void
    {
        $validator = Validator::make(
            ['value' => $value],
            ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->passes());
    }

    public static function provideDbTypeRangeFails(): array
    {
        return [
            'integer below min' => [
                'value' => -2147483649,
                'type' => 'integer',
                'message' => 'The value value must be within the integer range [-2147483648, 2147483647].',
            ],
            'integer above max' => [
                'value' => 2147483648,
                'type' => 'integer',
                'message' => 'The value value must be within the integer range [-2147483648, 2147483647].',
            ],
            'smallint below min' => [
                'value' => -32769,
                'type' => 'smallint',
                'message' => 'The value value must be within the smallint range [-32768, 32767].',
            ],
            'smallint above max' => [
                'value' => 32768,
                'type' => 'smallint',
                'message' => 'The value value must be within the smallint range [-32768, 32767].',
            ],
            'serial below min' => [
                'value' => 0,
                'type' => 'serial',
                'message' => 'The value value must be within the serial range [1, 2147483647].',
            ],
            'serial above max' => [
                'value' => 2147483648,
                'type' => 'serial',
                'message' => 'The value value must be within the serial range [1, 2147483647].',
            ],
            'varchar above max' => [
                'value' => str_repeat('a', 256),
                'type' => 'varchar',
                'message' => 'The value value must be within the varchar range [0, 255].',
            ],
        ];
    }

    #[DataProvider('provideDbTypeRangeFails')]
    public function testDbTypeRangeFails(mixed $value, string $type, ?string $message): void
    {
        $validator = Validator::make(
            ['value' => $value],
            ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->fails());

        $this->assertEquals($message, $validator->errors()->first('value'));
    }

    public function testDbTypeRangeObjectSyntaxPasses(): void
    {
        $validator = Validator::make(
            ['value' => 100],
            ['value' => [new DbTypeRangeRule(PostgresDatabaseTypeEnum::Integer->value)]],
        );

        $this->assertTrue($validator->passes());
    }

    public function testDbTypeRangeUnknownTypeThrows(): void
    {
        $this->assertExceptionThrew(
            expectedClassName: InvalidValidationRuleUsageException::class,
            expectedMessage: 'db_type_range: Unknown type',
            isStrict: false,
        );

        $validator = Validator::make(
            ['value' => 42],
            ['value' => 'db_type_range:unknown_type'],
        );

        $validator->passes();
    }

    public function testDbTypeRangeMissingTypeThrows(): void
    {
        $this->assertExceptionThrew(
            expectedClassName: InvalidValidationRuleUsageException::class,
            expectedMessage: 'db_type_range: The type parameter is required when checking the value field.',
        );

        $validator = Validator::make(
            ['value' => 42],
            ['value' => 'db_type_range'],
        );

        $validator->passes();
    }
}
