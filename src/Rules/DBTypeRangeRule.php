<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RonasIT\Support\Contracts\DBTypeResolverContract;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class DBTypeRangeRule implements ValidationRule
{
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

        $ranges = $this->resolver::ranges();

        if (!$this->resolver->hasType($this->type)) {
            throw new InvalidValidationRuleUsageException(
                message: "db_type_range: Unknown type '{$this->type}' for the {$attribute} field.",
            );
        }

        list($min, $max) = $ranges[$this->type];

        if ($this->resolver->isNumeric($this->type)) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                $fail("The {$attribute} must be an integer.");

                return;
            }

            $metric = (int) $value;
            $errorMessage = "The {$attribute} must be between {$min} and {$max}.";
        } elseif ($this->resolver->isString($this->type)) {
            if (!is_string($value)) {
                $fail("The {$attribute} must be a string.");

                return;
            }

            $metric = mb_strlen($value);
            $errorMessage = "The {$attribute} length must not exceed {$max} characters.";
        } else {
            return;
        }

        if ($metric < $min || $metric > $max) {
            $fail($errorMessage);
        }
    }
}
