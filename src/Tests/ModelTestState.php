<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;

class ModelTestState extends Assert
{
    protected Collection $state;
    protected Model $model;
    protected array $jsonFields;

    public function __construct(string $modelClassName)
    {
        $this->model = new $modelClassName();
        $this->state = $this->getDataSet($this->model->getTable());
        $this->jsonFields = $this->getModelJSONFields();
    }

    # done
    public function getJSONFields(): array
    {
        return $this->jsonFields;
    }

    # done
    public function getState(): Collection
    {
        return $this->state;
    }

    public function getFixturePath(string $fixture): string
    {
        $table = $this->model->getTable();

        return "changes/{$table}/{$fixture}";
    }

    public function getExpectedEmptyState(): array
    {
        return [
            'updated' => [],
            'created' => [],
            'deleted' => []
        ];
    }

    public function getChanges(): array
    {
        $updatedData = $this->getDataSet($this->model->getTable());

        $updatedRecords = [];
        $deletedRecords = [];

        $this->getState()->each(function ($originItem) use (&$updatedData, &$updatedRecords, &$deletedRecords) {
            $updatedItemIndex = $updatedData->search(function ($updatedItem) use ($originItem) {
                return $updatedItem['id'] === $originItem['id'];
            });

            if ($updatedItemIndex === false) {
                $deletedRecords[] = $originItem;
            } else {
                $updatedItem = $updatedData->get($updatedItemIndex);
                $changes = array_diff_assoc($updatedItem, $originItem);

                if (!empty($changes)) {
                    $updatedRecords[] = array_merge(['id' => $originItem['id']], $changes);
                }

                $updatedData->forget($updatedItemIndex);
            }
        });

        return [
            'updated' => $this->prepareChanges($updatedRecords),
            'created' => $this->prepareChanges($updatedData->values()->toArray()),
            'deleted' => $this->prepareChanges($deletedRecords)
        ];
    }

    protected function prepareChanges(array $changes): array
    {
        $jsonFields = Arr::wrap($this->getJSONFields());

        if (empty($jsonFields)) {
            return $changes;
        }

        return array_map(function ($item) use ($jsonFields) {
            foreach ($jsonFields as $jsonField) {
                if (Arr::has($item, $jsonField)) {
                    $item[$jsonField] = json_decode($item[$jsonField], true);
                }
            }

            return $item;
        }, $changes);
    }

    # done
    protected function getModelJSONFields(): array
    {
        $casts = $this->model->getCasts();

        $jsonCasts = array_filter($casts, fn ($cast) => $this->isJsonCast($cast));

        return array_keys($jsonCasts);
    }

    # done
    protected function isJsonCast(string $cast): bool
    {
        if ($cast === 'array') {
            return true;
        }

        return class_exists($cast) && is_subclass_of($cast, CastsAttributes::class);
    }

    # done
    protected function getDataSet(string $table, string $orderField = 'id'): Collection
    {
        return DB::table($table)
            ->orderBy($orderField)
            ->get()
            ->map(fn ($record) => (array) $record);
    }
}
