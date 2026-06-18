<?php

namespace RonasIT\Support\Testing;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ModelTestState extends TableTestState
{
    protected const array NATIVE_JSON_CASTS = ['array', 'json', 'object', 'collection'];

    protected array $classCastFields = [];

    /**
     * @param  class-string<Model>  $modelClassName
     */
    public function __construct(string $modelClassName)
    {
        $model = new $modelClassName();

        $this->classCastFields = $this->resolveClassCastFields($model);

        parent::__construct(
            tableName: $model->getTable(),
            jsonFields: $this->resolveNativeJsonFields($model),
            connectionName: $model->getConnectionName(),
            uniqueKey: $model->getKeyName(),
        );
    }

    protected function resolveNativeJsonFields(Model $model): array
    {
        return $this->getCastFieldsMatching($model, fn (string $type) => in_array($type, self::NATIVE_JSON_CASTS, true));
    }

    protected function resolveClassCastFields(Model $model): array
    {
        return $this->getCastFieldsMatching($model, fn (string $type) => $this->isClassCast($type));
    }

    protected function isClassCast(string $type): bool
    {
        return is_subclass_of($type, CastsAttributes::class)
            || is_subclass_of($type, Castable::class);
    }

    protected function prepareChanges(array $changes): array
    {
        if (!empty($this->classCastFields)) {
            $changes = array_map(fn (array $item) => $this->decodeClassCastFields($item), $changes);
        }

        return parent::prepareChanges($changes);
    }

    protected function decodeClassCastFields(array $item): array
    {
        foreach ($this->classCastFields as $field) {
            if (Arr::has($item, $field) && is_string($item[$field])) {
                $decoded = json_decode($item[$field], true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $item[$field] = $decoded;
                }
            }
        }

        return $item;
    }

    protected function getCastFieldsMatching(Model $model, callable $predicate): array
    {
        $matching = array_filter(
            array: $model->getCasts(),
            callback: fn (string $definition) => $predicate(Str::before($definition, ':')),
        );

        return array_keys($matching);
    }
}
