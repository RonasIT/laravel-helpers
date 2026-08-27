<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UniqueExceptOfAuthorizedUserRule implements ValidationRule
{
    public function __construct(
        protected string $table = 'users',
        protected string $keyField = 'id',
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table($this->table)
            ->where($this->keyField, '<>', Auth::id())
            ->whereIn($attribute, Arr::flatten((array) $value))
            ->exists();

        if ($exists) {
            $fail("The {$attribute} has already been taken.");
        }
    }

    public static function extend(string $attribute, mixed $value, array $parameters, ValidatorContract $validator): bool
    {
        $rule = new self(
            table: Arr::get($parameters, 0, 'users'),
            keyField: Arr::get($parameters, 1, 'id'),
        );

        $failed = false;

        $rule->validate($attribute, $value, function (string $message) use ($validator, &$failed) {
            $validator->addReplacer('unique_except_of_authorized_user', fn () => $message);
            $failed = true;
        });

        return !$failed;
    }
}
