<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RonasIT\Support\Contracts\DatabaseTypeRangesContract;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class DbTypeRangeRule implements ValidationRule
{
    protected DatabaseTypeRangesContract $rangesResolver;

    public function __construct(
        protected string $type,
    ) {
        $this->rangesResolver = app(DatabaseTypeRangesContract::class);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ranges = $this->rangesResolver->getRanges();

        if (!array_key_exists($this->type, $ranges)) {
            $available = implode(', ', array_keys($ranges));

            throw new InvalidValidationRuleUsageException(
                message: "db_type_range: Unknown type '{$this->type}' for the {$attribute} field. Available types: {$available}.",
            );
        }

        list($min, $max) = $ranges[$this->type];

        $checked = is_string($value) ? mb_strlen($value) : $value;

        if (($checked < $min) || ($checked > $max)) {
            $fail("The :attribute value must be within the {$this->type} range [{$min}, {$max}].");
        }
    }
}
