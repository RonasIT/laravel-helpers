<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;
use RonasIT\Support\Rules\DBTypeRangeRule;
use RonasIT\Support\Tests\Support\Mock\Resolvers\TestDBTypeResolver;
use RonasIT\Support\Tests\Support\Mock\Resolvers\TestDBTypeResolverWithUncategorizedTypes;
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
    private const float REAL_MIN = -3.4028234663852886e+38;
    private const float REAL_MAX = 3.4028234663852886e+38;
    private const float DOUBLE_MIN = -PHP_FLOAT_MAX;
    private const float DOUBLE_MAX = PHP_FLOAT_MAX;
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

    public static function provideDBTypeRangePasses(): array
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
            'real min' => [
                'value' => self::REAL_MIN,
                'type' => 'real',
            ],
            'real max' => [
                'value' => self::REAL_MAX,
                'type' => 'real',
            ],
            'real with decimal' => [
                'value' => 3.14,
                'type' => 'real',
            ],
            'real as string' => [
                'value' => '3.14',
                'type' => 'real',
            ],
            'double min' => [
                'value' => self::DOUBLE_MIN,
                'type' => 'double',
            ],
            'double max' => [
                'value' => self::DOUBLE_MAX,
                'type' => 'double',
            ],
            'double with decimal' => [
                'value' => 3.14,
                'type' => 'double',
            ],
            'null to integer' => [
                'value' => null,
                'type' => 'integer',
            ],
            'null to varchar' => [
                'value' => null,
                'type' => 'varchar',
            ],
            'null to real' => [
                'value' => null,
                'type' => 'real',
            ],
            'null to double' => [
                'value' => null,
                'type' => 'double',
            ],
        ];
    }

    #[DataProvider('provideDBTypeRangePasses')]
    public function testDBTypeRangePasses(mixed $value, string $type): void
    {
        $validator = Validator::make(
            data: ['value' => $value],
            rules: ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->passes());
    }

    public static function provideNumericDBTypeRangeFails(): array
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
            'real below min' => [
                'value' => -4e+38,
                'type' => 'real',
                'range' => [self::REAL_MIN, self::REAL_MAX],
            ],
            'real above max' => [
                'value' => 4e+38,
                'type' => 'real',
                'range' => [self::REAL_MIN, self::REAL_MAX],
            ],
        ];
    }

    #[DataProvider('provideNumericDBTypeRangeFails')]
    public function testNumericDBTypeRangeFails(mixed $value, string $type, array $range): void
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

    public static function provideDBTypeRangeWrongTypeFails(): array
    {
        return [
            'non-numeric string to integer' => [
                'value' => 'abc',
                'type' => 'integer',
                'error' => 'The value must be numeric.',
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

    #[DataProvider('provideDBTypeRangeWrongTypeFails')]
    public function testDBTypeRangeWrongTypeFails(mixed $value, string $type, string $error): void
    {
        $validator = Validator::make(
            data: ['value' => $value],
            rules: ['value' => "db_type_range:{$type}"],
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals($error, $validator->errors()->first('value'));
    }

    public function testVarcharDBTypeRangeFails(): void
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

    public function testDBTypeRangeObjectSyntaxPasses(): void
    {
        $validator = Validator::make(
            data: ['value' => 0],
            rules: ['value' => [new DBTypeRangeRule('integer')]],
        );

        $this->assertTrue($validator->passes());
    }

    public function testDBTypeRangeObjectSyntaxFails(): void
    {
        $validator = Validator::make(
            data: ['value' => self::INTEGER_MAX + 1],
            rules: ['value' => [new DBTypeRangeRule('integer')]],
        );

        $this->assertTrue($validator->fails());

        $this->assertEquals(
            expected: sprintf('The value must be between %d and %d.', self::INTEGER_MIN, self::INTEGER_MAX),
            actual: $validator->errors()->first('value'),
        );
    }

    public function testDBTypeRangeUnknownTypeThrows(): void
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

    public function testDBTypeRangeMissingTypeThrows(): void
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

    public function testDBTypeRangeUncategorizedTypePasses(): void
    {
        app()->bind(DBTypeResolverContract::class, TestDBTypeResolverWithUncategorizedTypes::class);

        $validator = Validator::make(
            data: ['value' => 'not-a-number'],
            rules: ['value' => [new DBTypeRangeRule(TestDBTypeResolverWithUncategorizedTypes::DECIMAL)]],
        );

        $this->assertTrue($validator->passes());
    }

    public function testDBTypeRangeUsesCustomResolverRangesPasses(): void
    {
        app()->bind(DBTypeResolverContract::class, TestDBTypeResolver::class);

        $validator = Validator::make(
            data: ['value' => TestDBTypeResolver::INTEGER_MAX],
            rules: ['value' => [new DBTypeRangeRule(TestDBTypeResolver::INTEGER)]],
        );

        $this->assertTrue($validator->passes());
    }

    public function testDBTypeRangeUsesCustomResolverRangesFails(): void
    {
        app()->bind(DBTypeResolverContract::class, TestDBTypeResolver::class);

        $validator = Validator::make(
            data: ['value' => TestDBTypeResolver::INTEGER_MAX + 1],
            rules: ['value' => [new DBTypeRangeRule(TestDBTypeResolver::INTEGER)]],
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            expected: sprintf('The value must be between %d and %d.', TestDBTypeResolver::INTEGER_MIN, TestDBTypeResolver::INTEGER_MAX),
            actual: $validator->errors()->first('value'),
        );
    }
}
