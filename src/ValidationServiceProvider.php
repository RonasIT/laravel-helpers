<?php

namespace RonasIT\Support;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;
use RonasIT\Support\Rules\DBTypeRangeRule;
use RonasIT\Support\Rules\ListExistsRule;
use RonasIT\Support\Rules\UniqueExceptOfAuthorizedUserRule;

class ValidationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->extendValidator();
    }

    protected function extendValidator(): void
    {
        Validator::extend('unique_except_of_authorized_user', function ($attribute, $value, $parameters, $validator) {
            $table = Arr::get($parameters, 0, 'users');
            $keyField = Arr::get($parameters, 1, 'id');

            return $this->runRule(
                rule: new UniqueExceptOfAuthorizedUserRule($table, $keyField),
                ruleName: 'unique_except_of_authorized_user',
                attribute: $attribute,
                value: $value,
                validator: $validator,
            );
        });

        Validator::extend('list_exists', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) < 1) {
                throw new InvalidValidationRuleUsageException("list_exists: At least 1 parameter must be added when checking the {$attribute} field in the request.");
            }

            $table = Arr::get($parameters, 0);
            $keyField = Arr::get($parameters, 1, 'id');
            $fieldName = Arr::get($parameters, 2);

            return $this->runRule(
                rule: new ListExistsRule($table, $keyField, $fieldName),
                ruleName: 'list_exists',
                attribute: $attribute,
                value: $value,
                validator: $validator,
            );
        });

        Validator::extend('db_type_range', function ($attribute, $value, $parameters, $validator) {
            $typeName = Arr::get($parameters, 0);

            if (empty($typeName)) {
                throw new InvalidValidationRuleUsageException(
                    message: "db_type_range: The type parameter is required when checking the {$attribute} field.",
                );
            }

            return $this->runRule(
                rule: new DBTypeRangeRule($typeName),
                ruleName: 'db_type_range',
                attribute: $attribute,
                value: $value,
                validator: $validator,
            );
        });
    }

    protected function runRule(
        ValidationRule $rule,
        string $ruleName,
        string $attribute,
        mixed $value,
        ValidatorContract $validator,
    ): bool {
        $failed = false;

        $rule->validate($attribute, $value, function (string $message) use ($validator, $ruleName, &$failed) {
            $validator->addReplacer($ruleName, fn () => $message);
            $failed = true;
        });

        return !$failed;
    }
}
