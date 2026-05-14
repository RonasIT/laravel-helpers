<?php

namespace RonasIT\Support\Testing;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ModelTestState extends TableTestState
{
    /**
     * Map of field names to their cast definitions.
     *
     * @var array<string, string>
     */
    protected array $customCastFields;

    /**
     * @param  class-string<Model>  $modelClassName
     */
    public function __construct(
        protected string $modelClassName,
    ) {
        $model = new $this->modelClassName();

        $casts = $model->getCasts();

        parent::__construct(
            tableName: $model->getTable(),
            jsonFields: $this->getNativeJsonFields($casts),
            connectionName: $model->getConnectionName(),
            uniqueKey: $model->getKeyName(),
        );

        $this->customCastFields = $this->getCustomCastFields($casts);
    }

    protected function getNativeJsonFields(array $casts): array
    {
        $nativeCasts = array_filter($casts, fn (string $castType): bool => $this->isNativeJsonCast($castType));

        return array_keys($nativeCasts);
    }

    protected function getCustomCastFields(array $casts): array
    {
        return array_filter($casts, fn (string $castType): bool => $this->isCustomCast($castType));
    }

    protected function isNativeJsonCast(string $castType): bool
    {
        return in_array($castType, ['array', 'json', 'object', 'collection']);
    }

    protected function isCustomCast(string $castType): bool
    {
        $castClass = $this->resolveCastClass($castType);

        return class_exists($castClass) && is_subclass_of($castClass, CastsAttributes::class);
    }

    protected function resolveCastClass(string $castDefinition): string
    {
        return str_contains($castDefinition, ':')
            ? explode(':', $castDefinition, 2)[0]
            : $castDefinition;
    }

    protected function resolveCaster(string $castDefinition): CastsAttributes
    {
        $arguments = [];

        if (str_contains($castDefinition, ':')) {
            list($castClass, $argString) = explode(':', $castDefinition, 2);

            $arguments = explode(',', $argString);
        } else {
            $castClass = $castDefinition;
        }

        return new $castClass(...$arguments);
    }

    protected function prepareChanges(array $changes): array
    {
        if (!empty($this->customCastFields)) {
            $changes = array_map(fn (array $changesItem) => $this->applyCustomCasts($changesItem), $changes);
        }

        return parent::prepareChanges($changes);
    }

    protected function applyCustomCasts(array $item): array
    {
        $attributes = $this->resolveModelAttributes($item);

        $model = new $this->modelClassName();
        $model->setRawAttributes($attributes);

        foreach ($this->customCastFields as $field => $castDefinition) {
            if (Arr::has($item, $field)) {
                $item[$field] = $this
                    ->resolveCaster($castDefinition)
                    ->get($model, $field, $attributes[$field], $attributes);
            }
        }

        return $item;
    }

    protected function resolveModelAttributes(array $item): array
    {
        if (!array_key_exists($this->uniqueKey, $item)) {
            return $item;
        }

        $original = $this->state->first(
            callback: fn (array $record) => $record[$this->uniqueKey] === $item[$this->uniqueKey],
        );

        return is_null($original)
            ? $item
            : array_merge($original, $item);
    }
}
