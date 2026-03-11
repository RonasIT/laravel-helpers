<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RonasIT\Support\Contracts\DatabaseTypeRangesContract;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class DbTypeRangeRule implements ValidationRule
{
    protected array $ranges;

    public function __construct(
        protected string $type,
    ) {
        $resolver = app(DatabaseTypeRangesContract::class);

        $this->ranges = $resolver::ranges();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!array_key_exists($this->type, $this->ranges)) {
            $available = implode(', ', array_keys($this->ranges));

            throw new InvalidValidationRuleUsageException(
                message: "db_type_range: Unknown type '{$this->type}' for the {$attribute} field. Available types: {$available}.",
            );
        }

        list($min, $max) = $this->ranges[$this->type];

        if (is_string($value)) {
            $metric = mb_strlen($value);
            $errorMessage = "The {$attribute} length must be between {$min} and {$max} characters.";
        } else {
            $metric = $value;
            $errorMessage = "The {$attribute} must be between {$min} and {$max}.";
        }

        if (($metric < $min) || ($metric > $max)) {
            $fail($errorMessage);
        }
    }
}
