<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Assert;
use RonasIT\Support\Traits\FixturesTrait;

class ModelTestState extends Assert
{
    use FixturesTrait;

    protected Collection $state;
    protected Model $model;
    protected array $jsonFields;

    public function __construct(string $modelClassName)
    {
        $this->model = new $modelClassName();
        $this->state = $this->getDataSet($this->model->getTable());
        $this->jsonFields = $this->getModelJSONFields();
    }

    public function assertNotChanged(): void
    {
        $changes = $this->getChanges();

        $this->assertEquals([
            'updated' => [],
            'created' => [],
            'deleted' => []
        ], $changes);
    }

    public function assertChangesEqualsFixture(string $fixture, bool $exportMode = false): void
    {
        $changes = $this->getChanges();

        $this->assertEqualsFixture($fixture, $changes, $exportMode);
    }

    protected function getChanges(): array
    {
        $updatedData = $this->getDataSet($this->model->getTable());

        $updatedRecords = [];
        $deletedRecords = [];

        $this->state->each(function ($originItem) use (&$updatedData, &$updatedRecords, &$deletedRecords) {
            $updatedItemIndex = $updatedData->search(fn ($updatedItem) => $updatedItem['id'] === $originItem['id']);

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
        $jsonFields = Arr::wrap($this->jsonFields);

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

    protected function getModelJSONFields(): array
    {
        $casts = $this->model->getCasts();

        $jsonCasts = array_filter($casts, fn ($cast) => $this->isJsonCast($cast));

        return array_keys($jsonCasts);
    }

    protected function isJsonCast(string $cast): bool
    {
        return ($cast === 'array') || (class_exists($cast) && is_subclass_of($cast, CastsAttributes::class));
    }

    protected function getFixturePath(string $fixtureName): string
    {
        $class = get_class($this);
        $explodedClass = explode('\\', $class);
        $className = Arr::last($explodedClass);
        $table = $this->model->getTable();

        return base_path("tests/fixtures/{$className}/changes/{$table}/{$fixtureName}");
    }

    protected function getDataSet(string $table, string $orderField = 'id'): Collection
    {
        return DB::table($table)
            ->orderBy($orderField)
            ->get()
            ->map(fn ($record) => (array) $record);
    }
}
