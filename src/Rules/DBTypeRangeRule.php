<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Support\Arr;
use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Enums\DBTypeCategoryEnum;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class DBTypeRangeRule extends ValidatorExtensionRule
{
    private const string INTEGER_PATTERN = '/^-?\d+$/';

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

        list($min, $max) = $this->resolver->getRange($this->type);

        match ($this->resolver->getTypeCategory($this->type)) {
            DBTypeCategoryEnum::Integer => $this->validateInteger($attribute, $value, $min, $max, $fail),
            DBTypeCategoryEnum::Float => $this->validateFloat($attribute, $value, $min, $max, $fail),
            DBTypeCategoryEnum::String => $this->validateString($attribute, $value, $max, $fail),
            default => null,
        };
    }

    protected function validateInteger(string $attribute, mixed $value, mixed $min, mixed $max, Closure $fail): void
    {
        if (!is_scalar($value) || !preg_match(self::INTEGER_PATTERN, (string) $value)) {
            $fail("The {$attribute} must be an integer.");

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

        if ((float) $value < $min || (float) $value > $max) {
            $fail("The {$attribute} must be between {$min} and {$max}.");
        }
    }

    protected function validateString(string $attribute, mixed $value, mixed $max, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("The {$attribute} must be a string.");

            return;
        }

        if (mb_strlen($value) > $max) {
            $fail("The {$attribute} length must not exceed {$max} characters.");
        }
    }

    protected static function ruleName(): string
    {
        return 'db_type_range';
    }

    protected static function fromParameters(array $parameters, string $attribute): static
    {
        $typeName = Arr::get($parameters, 0);

        if (empty($typeName)) {
            throw new InvalidValidationRuleUsageException(
                message: "db_type_range: The type parameter is required when checking the {$attribute} field.",
            );
        }

        return new self($typeName);
    }
}
