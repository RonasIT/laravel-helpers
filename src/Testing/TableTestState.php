<?php

namespace RonasIT\Support\Testing;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use RonasIT\Support\Exceptions\UnsupportedDBDriverException;
use RonasIT\Support\Traits\FixturesTrait;

class TableTestState extends Assert
{
    use FixturesTrait;

    protected string $tableName;
    protected array $jsonFields;
    protected ?string $connectionName;
    protected Collection $state;
    private array $binaryColumns;
    protected string $uniqueKey;

    protected const array BINARY_COLUMNS = [
        'bytea',
        'blob',
        'tinyblob',
        'mediumblob',
        'longblob',
        'binary',
        'varbinary',
    ];

    public function __construct(
        string $tableName,
        array $jsonFields = [],
        ?string $connectionName = null,
        string $uniqueKey = 'id',
    ) {
        $this->tableName = $tableName;
        $this->jsonFields = $jsonFields;
        $this->connectionName = $connectionName ?? DB::getDefaultConnection();
        $this->binaryColumns = $this->getBinaryColumns();
        $this->state = $this->getDataSet($tableName, $uniqueKey);
        $this->uniqueKey = $uniqueKey;
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
        $updatedData = $this->getDataSet($this->tableName, $this->uniqueKey);

        $updatedRecords = [];
        $deletedRecords = [];

        $this->state->each(function ($originItem) use (&$updatedData, &$updatedRecords, &$deletedRecords) {
            $updatedItemIndex = $updatedData->search(fn ($updatedItem) => $updatedItem[$this->uniqueKey] === $originItem[$this->uniqueKey]);

            if ($updatedItemIndex === false) {
                $deletedRecords[] = $originItem;
            } else {
                $updatedItem = $updatedData->get($updatedItemIndex);
                $changes = array_diff_assoc($updatedItem, $originItem);

                if (!empty($changes)) {
                    $updatedRecords[] = array_merge([$this->uniqueKey => $originItem[$this->uniqueKey]], $changes);
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
                $shouldDecode = Arr::has($item, $jsonField)
                    && is_string($item[$jsonField])
                    && json_validate($item[$jsonField]);

                    if ($shouldDecode) {
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
        $dataSet = DB::connection($this->connectionName)
            ->table($table)
            ->orderBy($orderField)
            ->get()
            ->map(fn ($record) => (array) $record);

        $this->prepareBinaryFields($dataSet);

        return $dataSet;
    }

    protected function prepareBinaryFields(Collection $dataSet): void
    {
        $dataSet->transform(function (array $record) {
            array_walk($record, function (mixed &$value, string $field) {
                if (!is_null($value) && in_array($field, $this->binaryColumns)) {
                    $value = $this->normalizeBinaryValue($value);
                }
            });

            return $record;
        });
    }

    protected function normalizeBinaryValue(mixed $value): ?string
    {
        if (get_debug_type($value) === 'resource (closed)') {
            return null;
        }

        if (is_resource($value)) {
            $metadata = stream_get_meta_data($value);

            if ($metadata['seekable'] ?? false) {
                rewind($value);
            }

            $value = stream_get_contents($value);

            return ($value === false) ? null : bin2hex($value);
        }

        return is_string($value) ? bin2hex($value) : null;
    }

    protected function getBinaryColumns(): array
    {
        $connection = DB::connection($this->connectionName);

        $tableSchema = $this->getTableSchema($connection->getDriverName(), $connection->getDatabaseName());

        return $connection
            ->table('information_schema.columns')
            ->select('column_name')
            ->where('table_name', $this->tableName)
            ->whereIn('table_schema', $tableSchema)
            ->whereIn('data_type', self::BINARY_COLUMNS)
            ->get()
            ->pluck('column_name')
            ->toArray();
    }

    protected function getTableSchema(string $driverName, string $databaseName): array
    {
        $tableSchema = match ($driverName) {
            'pgsql' => config("database.connections.{$this->connectionName}.schema")
                ?? config("database.connections.{$this->connectionName}.search_path", 'public'),
            'mysql' => $databaseName,
            default => throw new UnsupportedDBDriverException($driverName),
        };

        $tableSchema = array_filter(explode(',', $tableSchema), fn ($schema) => !empty($schema));

        return Arr::wrap($tableSchema);
    }
}
