<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
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
}
