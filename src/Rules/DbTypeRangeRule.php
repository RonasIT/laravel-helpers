<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RonasIT\Support\Contracts\DatabaseTypeRangesContract;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class DbTypeRangeRule implements ValidationRule
{
    /**
     * @var class-string<DatabaseTypeRangesContract>
     */
    protected string $resolver;

    public function __construct(
        protected string $type,
    ) {
        $this->resolver = app(DatabaseTypeRangesContract::class);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ranges = $this->resolver::ranges();

        if (!array_key_exists($this->type, $ranges)) {
            $availableTypes = implode(', ', array_keys($ranges));

            throw new InvalidValidationRuleUsageException(
                message: "db_type_range: Unknown type '{$this->type}' for the {$attribute} field. Available types: {$availableTypes}.",
            );
        }

        list($min, $max) = $ranges[$this->type];

        if (in_array($this->type, $this->resolver::integerTypes())) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                $fail("The {$attribute} must be an integer.");

                return;
            }

            $metric = (int) $value;
            $errorMessage = "The {$attribute} must be between {$min} and {$max}.";
        } elseif (in_array($this->type, $this->resolver::stringTypes())) {
            if (!is_string($value)) {
                $fail("The {$attribute} must be a string.");

                return;
            }

            $metric = mb_strlen($value);
            $errorMessage = "The {$attribute} length must be less than {$max} characters.";
        } else {
            return;
        }

        if ($metric < $min || $metric > $max) {
            $fail($errorMessage);
        }
    }
}
