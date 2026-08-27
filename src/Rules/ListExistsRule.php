<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RonasIT\Support\Exceptions\InvalidValidationRuleUsageException;

class ListExistsRule implements ValidationRule
{
    public function __construct(
        protected string $table,
        protected string $keyField = 'id',
        protected ?string $fieldName = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail("The {$attribute} field must be an array.");

            return;
        }

        if (is_multidimensional($value) && empty($this->fieldName)) {
            throw new InvalidValidationRuleUsageException("list_exists: The third parameter should be filled when checking the {$attribute} field if we are using a collection in request.");
        }

        $values = !empty($this->fieldName) ? Arr::pluck($value, $this->fieldName) : $value;

        $values = array_unique($values);

        $existingValueCount = DB::table($this->table)
            ->whereIn($this->keyField, $values)
            ->distinct()
            ->count($this->keyField);

        if ($existingValueCount !== count($values)) {
            $fail("Some of the passed {$attribute} are not exists.");
        }
    }
}
