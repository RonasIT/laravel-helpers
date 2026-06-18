<?php

namespace RonasIT\Support\Testing;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ModelTestState extends TableTestState
{
    protected const array NATIVE_JSON_CASTS = ['array', 'json', 'object', 'collection'];

    protected Model $model;

    protected array $classCastFields = [];

    /**
     * @param  class-string<Model>  $modelClassName
     */
    public function __construct(string $modelClassName)
    {
        $this->model = new $modelClassName();

        $this->classCastFields = $this->resolveClassCastFields();

        parent::__construct(
            tableName: $this->model->getTable(),
            jsonFields: $this->resolveNativeJsonFields(),
            connectionName: $this->model->getConnectionName(),
            uniqueKey: $this->model->getKeyName(),
        );
    }

    protected function resolveNativeJsonFields(): array
    {
        return $this->getCastFieldsMatching(fn (string $type) => in_array($type, self::NATIVE_JSON_CASTS, true));
    }

    protected function resolveClassCastFields(): array
    {
        return $this->getCastFieldsMatching(fn (string $type) =>
            is_subclass_of($type, CastsAttributes::class)
            || is_subclass_of($type, Castable::class)
        );
    }

    protected function prepareChanges(array $changes): array
    {
        if (!empty($this->classCastFields)) {
            $changes = array_map(fn (array $item) => $this->applyClassCasts($item), $changes);
        }

        return parent::prepareChanges($changes);
    }

    protected function applyClassCasts(array $item): array
    {
        $model = clone $this->model;

        $model->setRawAttributes($this->resolveRawAttributes($item));

        foreach ($this->classCastFields as $field) {
            if (array_key_exists($field, $item) && $this->isJsonBacked($item[$field])) {
                $item[$field] = $model->getAttribute($field);
            }
        }

        return $item;
    }

    protected function isJsonBacked(mixed $raw): bool
    {
        if (!is_string($raw)) {
            return false;
        }

        return is_array(json_decode($raw, true));
    }

    protected function resolveRawAttributes(array $item): array
    {
        $original = $this->state->firstWhere($this->uniqueKey, $item[$this->uniqueKey]);

        return (is_null($original))
            ? $item
            : array_merge($original, $item);
    }

    protected function getCastFieldsMatching(callable $predicate): array
    {
        $matching = array_filter(
            array: $this->model->getCasts(),
            callback: fn (string $definition) => $predicate(Str::before($definition, ':')),
        );

        return array_keys($matching);
    }
}
