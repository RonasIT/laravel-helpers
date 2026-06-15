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

        return array_map(fn (array $item) => $this->applyCasts($item), $changes);
    }

    protected function applyCasts(array $item): array
    {
        $attributes = $this->resolveModelAttributes($item);

        $this->model->setRawAttributes($attributes);

        foreach ($this->castFields as $field) {
            if (Arr::has($item, $field)) {
                $item[$field] = $this->model->getAttribute($field);
            }
        }

        return $item;
    }

    protected function resolveModelAttributes(array $item): array
    {
        $original = $this->state->firstWhere($this->uniqueKey, $item[$this->uniqueKey]);

        return is_null($original)
            ? $item
            : array_merge($original, $item);
    }
}
