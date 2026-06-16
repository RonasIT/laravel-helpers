<?php

namespace RonasIT\Support\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ModelTestState extends TableTestState
{
    protected Model $model;

    protected array $castFields;

    /**
     * @param  class-string<Model>  $modelClassName
     */
    public function __construct(string $modelClassName)
    {
        $this->model = new $modelClassName();

        parent::__construct(
            tableName: $this->model->getTable(),
            connectionName: $this->model->getConnectionName(),
            uniqueKey: $this->model->getKeyName(),
        );

        $this->castFields = array_keys($this->model->getCasts());
    }

    protected function prepareChanges(array $changes): array
    {
        if (empty($this->castFields)) {
            return $changes;
        }

        return array_map(fn (array $item) => $this->applyJsonCasts($item), $changes);
    }

    protected function applyJsonCasts(array $item): array
    {
        $model = clone $this->model;

        $attributes = $this->resolveRawAttributes($item);

        $model->setRawAttributes($attributes);

        foreach ($this->castFields as $field) {
            if (Arr::has($item, $field) && $this->isJsonBacked($item[$field])) {
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
}
