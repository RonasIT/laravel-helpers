<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use RonasIT\Support\Traits\FixturesTrait;

class BaseTestState extends Assert
{
    use FixturesTrait;

    protected Collection $state;

    public function __construct(
        protected string $tableName,
        protected array $jsonFields,
        protected ?string $connectionName,
    ) {
        $this->state = $this->getDataSet($this->tableName);
    }

    public function assertNotChanged(): void
    {
        $changes = $this->getChanges();

        $this->assertEquals([
            'updated' => [],
            'created' => [],
            'deleted' => [],
        ], $changes);
    }

    public function assertChangesEqualsFixture(string $fixture, bool $exportMode = false): void
    {
        $changes = $this->getChanges();

        $this->assertEqualsFixture($fixture, $changes, $exportMode);
    }

    protected function getChanges(): array
    {
        $updatedData = $this->getDataSet($this->tableName);

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
            'deleted' => $this->prepareChanges($deletedRecords),
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

    protected function getFixturePath(string $fixtureName): string
    {
        $testClassTrace = Arr::first(debug_backtrace(), fn ($trace) => str_ends_with($trace['file'], 'Test.php'));
        $testFileName = Arr::last(explode('/', $testClassTrace['file']));
        $testClass = Str::remove('.php', $testFileName);

        return base_path("tests/fixtures/{$testClass}/db_changes/{$this->tableName}/{$fixtureName}");
    }

    protected function getDataSet(string $table, string $orderField = 'id'): Collection
    {
        return DB::connection($this->connectionName)
            ->table($table)
            ->orderBy($orderField)
            ->get()
            ->map(fn ($record) => (array) $record);
    }
}
