<?php

namespace RonasIT\Support\Rules;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UniqueExceptOfAuthorizedUserRule extends ValidatorExtensionRule
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

    protected static function ruleName(): string
    {
        return 'unique_except_of_authorized_user';
    }

    protected static function fromParameters(array $parameters, string $attribute): static
    {
        return new self(
            table: Arr::get($parameters, 0, 'users'),
            keyField: Arr::get($parameters, 1, 'id'),
        );
    }
}
