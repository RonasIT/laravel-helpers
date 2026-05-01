<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Enums\DBTypeCategoryEnum;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class DBTypeRangeRule implements ValidationRule
{
    protected const string INTEGER_REGEX = '/^-?\d+$/';

    protected DBTypeResolverContract $resolver;

    public function __construct(
        protected string $type,
    ) {
        $this->resolver = app(DBTypeResolverContract::class);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            return;
        }

        if (!$this->resolver->hasType($this->type)) {
            throw new InvalidValidationRuleUsageException(
                message: "db_type_range: Unknown type '{$this->type}' for the {$attribute} field.",
            );
        }

        $ranges = $this->resolver::ranges();

        list($min, $max) = $ranges[$this->type];

        match (true) {
            $this->resolver->isTypeCategory(DBTypeCategoryEnum::Integer, $this->type) => $this->validateInteger($attribute, $value, $min, $max, $fail),
            $this->resolver->isTypeCategory(DBTypeCategoryEnum::BigInteger, $this->type) => $this->validateBigInteger($attribute, $value, $min, $max, $fail),
            $this->resolver->isTypeCategory(DBTypeCategoryEnum::Float, $this->type) => $this->validateFloat($attribute, $value, $min, $max, $fail),
            $this->resolver->isTypeCategory(DBTypeCategoryEnum::String, $this->type) => $this->validateString($attribute, $value, $min, $max, $fail),
            default => null,
        };
    }

    protected function validateInteger(string $attribute, mixed $value, mixed $min, mixed $max, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail("The {$attribute} must be numeric.");

            return;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => $min,
                'max_range' => $max,
            ],
        ]);

        if ($result === false) {
            $fail("The {$attribute} must be between {$min} and {$max}.");
        }
    }

    protected function validateBigInteger(string $attribute, mixed $value, mixed $min, mixed $max, Closure $fail): void
    {
        if (!preg_match(self::INTEGER_REGEX, (string) $value)) {
            $fail("The {$attribute} must be numeric.");

            return;
        }

        $tooSmall = bccomp((string) $value, (string) $min) === -1;
        $tooBig = bccomp((string) $value, (string) $max) === 1;

        if ($tooSmall || $tooBig) {
            $fail("The {$attribute} must be between {$min} and {$max}.");
        }
    }

    protected function validateFloat(string $attribute, mixed $value, mixed $min, mixed $max, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail("The {$attribute} must be numeric.");

            return;
        }

        $metric = (float) $value;

        if ($metric < $min || $metric > $max) {
            $fail("The {$attribute} must be between {$min} and {$max}.");
        }
    }

    protected function validateString(string $attribute, mixed $value, mixed $min, mixed $max, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("The {$attribute} must be a string.");

            return;
        }

        $metric = mb_strlen($value);

        if ($metric < $min || $metric > $max) {
            $fail("The {$attribute} length must not exceed {$max} characters.");
        }
    }
}
