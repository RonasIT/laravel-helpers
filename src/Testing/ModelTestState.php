<?php

namespace RonasIT\Support\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ModelTestState extends TableTestState
{
    protected const array NATIVE_JSON_CASTS = ['array', 'json', 'object', 'collection'];

    protected Model $model;

    protected array $jsonCastFields = [];

    protected array $classCastFields = [];

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

        $this->resolveCastFields();
    }

    protected function resolveCastFields(): void
    {
        foreach ($this->model->getCasts() as $field => $definition) {
            $type = explode(':', $definition, 2)[0];

            if (in_array(strtolower($type), self::NATIVE_JSON_CASTS, true)) {
                $this->jsonCastFields[] = $field;
            } elseif (class_exists($type)) {
                $this->classCastFields[] = $field;
            }
        }
    }

    protected function prepareChanges(array $changes): array
    {
        if (empty($this->jsonCastFields) && empty($this->classCastFields)) {
            return $changes;
        }

        return array_map(fn (array $item) => $this->applyJsonCasts($item), $changes);
    }

    protected function applyJsonCasts(array $item): array
    {
        $model = clone $this->model;

        $model->setRawAttributes($this->resolveRawAttributes($item));

        foreach ($this->jsonCastFields as $field) {
            if (Arr::has($item, $field) && is_string($item[$field])) {
                $item[$field] = $model->getAttribute($field);
            }
        }

        foreach ($this->classCastFields as $field) {
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
