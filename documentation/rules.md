[<< Iterators][1]

# Validation rules

`ValidationServiceProvider` registers a set of custom validation rules on boot. If your app relies on Laravel's
package auto-discovery, this happens automatically. Otherwise you need to add
`RonasIT\Support\ValidationServiceProvider::class` to `config/app.php` yourself (see the
[3.9 migration note][2] for details).

Every rule below can be used either with Laravel's string syntax (`'field' => 'rule_name:param1,param2'`) or with
the object syntax (`'field' => [new SomeRule(...)]`).

## unique_except_of_authorized_user

`RonasIT\Support\Rules\UniqueExceptOfAuthorizedUserRule`

Fails if any other row (any row except the currently authorized user's own) already has the given value(s) in the
checked column. Useful for "unique, but ignore my own record" checks, e.g. when a user updates their own email.

```
'email' => 'unique_except_of_authorized_user:users,id'
```

* `table` (optional, default `users`) - table to check.
* `keyField` (optional, default `id`) - primary key column excluded for the currently authorized user (via
  `Auth::id()`).

## list_exists

`RonasIT\Support\Rules\ListExistsRule`

Fails unless every value passed in an array field exists in the given table's column. Accepts either a flat array
of values, or an array of objects/collections, in which case the third parameter tells the rule which field to
pluck values from.

```
'ids' => 'list_exists:clients,id'

// or, for a collection of objects:
'items' => 'list_exists:clients,id,id'
```

* `table` (required) - table to check against.
* `keyField` (optional, default `id`) - column to check against.
* `fieldName` (optional) - required when the field value is an array of objects/collections; the field to pluck
  from each item before checking.

## db_type_range

`RonasIT\Support\Rules\DBTypeRangeRule`

Fails unless the value fits the numeric or string range of the given database column type. Useful for validating
request input before it hits a database insert/update, so out-of-range values fail with a clear message instead of
a database error.

```
'age' => 'db_type_range:smallint'
```

* `type` (required) - one of `smallint`, `integer`, `bigint`, `smallserial`, `serial`, `bigserial`, `real`,
  `double`, `varchar`. The ranges are resolved via the `DBTypeResolverContract` binding (`PostgresDBTypeResolver`
  by default).

## Adding a new rule

Every rule class extends the abstract `RonasIT\Support\Rules\BaseValidationRule`, which implements
`Illuminate\Contracts\Validation\ValidationRule` and provides the bridge between Laravel's string-syntax
`Validator::extend()` API and the rule's own `validate()` method. A new rule only needs to implement three things:

* `validate(string $attribute, mixed $value, Closure $fail): void` - the actual validation logic, same as any
  other Laravel `ValidationRule`.
* `ruleName(): string` - the string name the rule is registered under (used to attach the failure message via
  `addReplacer()`).
* `fromParameters(array $parameters, string $attribute): static` - builds a rule instance from the string-syntax
  parameters (`'rule_name:param1,param2'`); this is also the right place to throw
  `InvalidValidationRuleUsageException` for misuse (e.g. a missing required parameter).

Then register it in `ValidationServiceProvider::extendValidator()`:

```php
Validator::extend('my_rule', MyRule::extend(...));
```

[<< Iterators][1]

[1]:iterators.md
[2]:migration.md#3.9
