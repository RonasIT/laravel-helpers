<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Contracts\DatabaseTypeRangesContract;
use RonasIT\Support\Enums\PostgresDatabaseTypeEnum;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;
use RonasIT\Support\Rules\DbTypeRangeRule;
use RonasIT\Support\Tests\Support\Mock\Enums\CustomDatabaseTypeRangesEnum;
use RonasIT\Support\Tests\Support\Mock\Enums\DatabaseTypeRangesWithUncategorizedTypesEnum;
use RonasIT\Support\Tests\Support\Traits\SqlMockTrait;
use RonasIT\Support\Traits\TestingTrait;

class ValidatorTest extends TestCase
{
    use SqlMockTrait;
    use TestingTrait;

    private const int SMALLINT_MIN = -32768;
    private const int SMALLINT_MAX = 32767;
    private const int INTEGER_MIN = -2147483648;
    private const int INTEGER_MAX = 2147483647;
    private const int BIGINT_MIN = PHP_INT_MIN;
    private const int BIGINT_MAX = PHP_INT_MAX;
    private const int SERIAL_MIN = 1;
    private const int SERIAL_MAX = 2147483647;
    private const int SMALLSERIAL_MIN = 1;
    private const int SMALLSERIAL_MAX = 32767;
    private const int BIGSERIAL_MIN = 1;
    private const int BIGSERIAL_MAX = PHP_INT_MAX;
    private const int VARCHAR_MAX = 255;

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
                'value' => self::INTEGER_MIN,
                'type' => 'integer',
            ],
            'integer max' => [
                'value' => self::INTEGER_MAX,
                'type' => 'integer',
            ],
            'smallint min' => [
                'value' => self::SMALLINT_MIN,
                'type' => 'smallint',
            ],
            'smallint max' => [
                'value' => self::SMALLINT_MAX,
                'type' => 'smallint',
            ],
            'bigint min' => [
                'value' => self::BIGINT_MIN,
                'type' => 'bigint',
            ],
            'bigint max' => [
                'value' => self::BIGINT_MAX,
                'type' => 'bigint',
            ],
            'serial min' => [
                'value' => self::SERIAL_MIN,
                'type' => 'serial',
            ],
            'serial max' => [
                'value' => self::SERIAL_MAX,
                'type' => 'serial',
            ],
            'smallserial min' => [
                'value' => self::SMALLSERIAL_MIN,
                'type' => 'smallserial',
            ],
            'smallserial max' => [
                'value' => self::SMALLSERIAL_MAX,
                'type' => 'smallserial',
            ],
            'bigserial min' => [
                'value' => self::BIGSERIAL_MIN,
                'type' => 'bigserial',
            ],
            'bigserial max' => [
                'value' => self::BIGSERIAL_MAX,
                'type' => 'bigserial',
            ],
            'varchar min' => [
                'value' => '',
                'type' => 'varchar',
            ],
            'varchar max' => [
                'value' => str_repeat('a', self::VARCHAR_MAX),
                'type' => 'varchar',
            ],
            'text min' => [
                'value' => '',
                'type' => 'text',
            ],
            'integer max as string' => [
                'value' => (string) self::INTEGER_MAX,
                'type' => 'integer',
            ],
            'integer min as string' => [
                'value' => (string) self::INTEGER_MIN,
                'type' => 'integer',
            ],
            'smallint max as string' => [
                'value' => (string) self::SMALLINT_MAX,
                'type' => 'smallint',
            ],
        ];
    }

    public static function provideDbTypeRangeWrongTypeFails(): array
    {
        return [
            'non-numeric string to integer' => [
                'value' => 'abc',
                'type' => 'integer',
                'error' => 'The value must be an integer.',
            ],
            'float to integer' => [
                'value' => 42.5,
                'type' => 'integer',
                'error' => 'The value must be an integer.',
            ],
            'float string to integer' => [
                'value' => '42.5',
                'type' => 'integer',
                'error' => 'The value must be an integer.',
            ],
            'integer to varchar' => [
                'value' => 42,
                'type' => 'varchar',
                'error' => 'The value must be a string.',
            ],
            'array to varchar' => [
                'value' => ['foo'],
                'type' => 'varchar',
                'error' => 'The value must be a string.',
            ],
        ];
    }

    #[DataProvider('provideDbTypeRangePasses')]
    public function testDbTypeRangePasses(mixed $value, string $type): void
    {
        $validator = Validator::make(
            data: ['value' => $value],
            rules: ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->passes());
    }

    public static function provideNumericDbTypeRangeFails(): array
    {
        return [
            'integer below min' => [
                'value' => self::INTEGER_MIN - 1,
                'type' => 'integer',
                'range' => [self::INTEGER_MIN, self::INTEGER_MAX],
            ],
            'integer above max' => [
                'value' => self::INTEGER_MAX + 1,
                'type' => 'integer',
                'range' => [self::INTEGER_MIN, self::INTEGER_MAX],
            ],
            'smallint below min' => [
                'value' => self::SMALLINT_MIN - 1,
                'type' => 'smallint',
                'range' => [self::SMALLINT_MIN, self::SMALLINT_MAX],
            ],
            'smallint above max' => [
                'value' => self::SMALLINT_MAX + 1,
                'type' => 'smallint',
                'range' => [self::SMALLINT_MIN, self::SMALLINT_MAX],
            ],
            'serial below min' => [
                'value' => self::SERIAL_MIN - 1,
                'type' => 'serial',
                'range' => [self::SERIAL_MIN, self::SERIAL_MAX],
            ],
            'serial above max' => [
                'value' => self::SERIAL_MAX + 1,
                'type' => 'serial',
                'range' => [self::SERIAL_MIN, self::SERIAL_MAX],
            ],
            'smallserial below min' => [
                'value' => self::SMALLSERIAL_MIN - 1,
                'type' => 'smallserial',
                'range' => [self::SMALLSERIAL_MIN, self::SMALLSERIAL_MAX],
            ],
            'smallserial above max' => [
                'value' => self::SMALLSERIAL_MAX + 1,
                'type' => 'smallserial',
                'range' => [self::SMALLSERIAL_MIN, self::SMALLSERIAL_MAX],
            ],
            'bigserial below min' => [
                'value' => self::BIGSERIAL_MIN - 1,
                'type' => 'bigserial',
                'range' => [self::BIGSERIAL_MIN, self::BIGSERIAL_MAX],
            ],
        ];
    }

    #[DataProvider('provideNumericDbTypeRangeFails')]
    public function testNumericDbTypeRangeFails(mixed $value, string $type, array $range): void
    {
        $validator = Validator::make(
            data: ['value' => $value],
            rules: ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->fails());

        $this->assertEquals(
            expected: "The value must be between {$range[0]} and {$range[1]}.",
            actual: $validator->errors()->first('value'),
        );
    }

    #[DataProvider('provideDbTypeRangeWrongTypeFails')]
    public function testDbTypeRangeWrongTypeFails(mixed $value, string $type, string $error): void
    {
        $validator = Validator::make(
            data: ['value' => $value],
            rules: ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals($error, $validator->errors()->first('value'));
    }

    public function testVarcharDbTypeRangeFails(): void
    {
        $value = str_repeat('a', self::VARCHAR_MAX + 1);
        $max = self::VARCHAR_MAX;

        $validator = Validator::make(
            data: ['value' => $value],
            rules: ['value' => 'db_type_range:varchar'],
        );

        $this->assertTrue($validator->fails());

        $this->assertEquals(
            expected: "The value length must not exceed {$max} characters.",
            actual: $validator->errors()->first('value'),
        );
    }

    public function testDbTypeRangeObjectSyntaxPasses(): void
    {
        $validator = Validator::make(
            data: ['value' => 0],
            rules: ['value' => [new DbTypeRangeRule(PostgresDatabaseTypeEnum::Integer->value)]],
        );

        $this->assertTrue($validator->passes());
    }

    public function testDbTypeRangeObjectSyntaxFails(): void
    {
        $validator = Validator::make(
            data: ['value' => self::INTEGER_MAX + 1],
            rules: ['value' => [new DbTypeRangeRule(PostgresDatabaseTypeEnum::Integer->value)]],
        );

        $this->assertTrue($validator->fails());

        $this->assertEquals(
            expected: sprintf('The value must be between %d and %d.', self::INTEGER_MIN, self::INTEGER_MAX),
            actual: $validator->errors()->first('value'),
        );
    }

    public function testDbTypeRangeUnknownTypeThrows(): void
    {
        $this->assertExceptionThrew(
            expectedClassName: InvalidValidationRuleUsageException::class,
            expectedMessage: 'db_type_range: Unknown type',
            isStrict: false,
        );

        $validator = Validator::make(
            data: ['value' => 0],
            rules: ['value' => 'db_type_range:unknown_type'],
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
            data: ['value' => 0],
            rules: ['value' => 'db_type_range'],
        );

        $validator->passes();
    }

    public function testDbTypeRangeUncategorizedTypePasses(): void
    {
        app()->bind(DatabaseTypeRangesContract::class, fn () => DatabaseTypeRangesWithUncategorizedTypesEnum::class);

        $validator = Validator::make(
            data: ['value' => 3.14],
            rules: ['value' => [new DbTypeRangeRule('decimal')]],
        );

        $this->assertTrue($validator->passes());
    }

    public function testDbTypeRangeUsesCustomResolverRangesPasses(): void
    {
        app()->bind(DatabaseTypeRangesContract::class, fn () => CustomDatabaseTypeRangesEnum::class);

        $passing = Validator::make(
            data: ['value' => 50],
            rules: ['value' => [new DbTypeRangeRule('integer')]],
        );

        $this->assertTrue($passing->passes());
    }

    public function testDbTypeRangeUsesCustomResolverRangesFails(): void
    {
        app()->bind(DatabaseTypeRangesContract::class, fn () => CustomDatabaseTypeRangesEnum::class);

        $failing = Validator::make(
            data: ['value' => 101],
            rules: ['value' => [new DbTypeRangeRule('integer')]],
        );

        $this->assertTrue($failing->fails());
        $this->assertEquals('The value must be between 0 and 100.', $failing->errors()->first('value'));
    }
}
