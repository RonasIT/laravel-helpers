<?php

namespace RonasIT\Support\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

abstract class BaseValidationRule implements ValidationRule
{
    public static function extend(string $attribute, mixed $value, array $parameters, ValidatorContract $validator): bool
    {
        $success = true;

        static::fromParameters($parameters, $attribute)->validate(
            attribute: $attribute,
            value: $value,
            fail: function (string $message) use ($validator, &$success) {
                $validator->addReplacer(
                    static::ruleName(),
                    fn (string $resolvedMessage) => $resolvedMessage === 'validation.' . static::ruleName()
                        ? $message
                        : $resolvedMessage,
                );

                $success = false;
            },
        );

        return $success;
    }

    abstract protected static function ruleName(): string;

    abstract protected static function fromParameters(array $parameters, string $attribute): static;
}
