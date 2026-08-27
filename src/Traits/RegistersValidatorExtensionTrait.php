<?php

namespace RonasIT\Support\Traits;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;

trait RegistersValidatorExtensionTrait
{
    // Relies on a RULE_NAME constant defined by the using class; PHP has no way to enforce that for a trait constant.
    public static function extend(string $attribute, mixed $value, array $parameters, ValidatorContract $validator): bool
    {
        $failed = false;

        static::fromParameters($parameters, $attribute)->validate($attribute, $value, function (string $message) use ($validator, &$failed) {
            $validator->addReplacer(static::RULE_NAME, fn () => $message);
            $failed = true;
        });

        return !$failed;
    }

    abstract protected static function fromParameters(array $parameters, string $attribute): static;
}
