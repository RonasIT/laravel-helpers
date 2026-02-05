<?php

namespace RonasIT\Support\Testing;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use RonasIT\Support\Traits\FixturesTrait;

class TableTestState extends Assert
{
    use FixturesTrait;

    protected string $tableName;
    protected array $jsonFields;
    protected ?string $connectionName;
    protected Collection $state;

    public function __construct(
        string $tableName,
        array $jsonFields = [],
        ?string $connectionName = null,
    ) {
        $this->tableName = $tableName;
        $this->jsonFields = $jsonFields;
        $this->connectionName = $connectionName ?? DB::getDefaultConnection();
        $this->state = $this->getDataSet($tableName);
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
                    $changes = Arr::map($changes, fn ($field) => ($this->isBinary($field)) ? bin2hex($field) : $field);

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

    protected function isBinary(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        if (str_contains($value, "\0")) {
            return true;
        }

        $sample = substr($value, 0, 8192);

        if (strlen($sample) === 0 || mb_check_encoding($sample, 'UTF-8')) {
            return false;
        }

        return !ctype_print($sample) && !preg_match('//u', $sample);
    }

    protected function prepareChanges(array $changes): array
    {
        $jsonFields = Arr::wrap($this->jsonFields);

        if (empty($jsonFields)) {
            return $changes;
        }

        return array_map(function ($item) use ($jsonFields) {
            foreach ($jsonFields as $jsonField) {
                $isJsonField = Arr::has($item, $jsonField)
                    && is_string($item[$jsonField])
                    && json_validate($item[$jsonField]);

                if ($isJsonField) {
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
