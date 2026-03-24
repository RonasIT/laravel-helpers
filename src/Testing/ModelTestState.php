<?php

namespace RonasIT\Support\Testing;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Arr;

class ModelTestState extends TableTestState
{
    /**
     * Map of field names to their custom cast class names.
     *
     * @var array<string, class-string<CastsAttributes>>
     */
    protected array $customCastFields;

    public function __construct(string $modelClassName)
    {
        $model = new $modelClassName();
        $casts = $model->getCasts();

        parent::__construct(
            tableName: $model->getTable(),
            jsonFields: $this->getNativeJsonFields($casts),
            connectionName: $model->getConnectionName(),
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
        return class_exists($castType) && is_subclass_of($castType, CastsAttributes::class);
    }

    protected function prepareChanges(array $changes): array
    {
        $changes = parent::prepareChanges($changes);

        if (empty($this->customCastFields)) {
            return $changes;
        }

        return array_map(function ($item) {
            foreach ($this->customCastFields as $field => $castClass) {
                if (Arr::has($item, $field)) {
                    $item[$field] = (new $castClass())->get(null, $field, $item[$field], $item);
                }
            }

            return $item;
        }, $changes);
    }
}
