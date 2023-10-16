<?php

namespace RonasIT\Support\Traits;

use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait NovaTestTrait
{
    protected static array $jsonFields = [];

    public function novaActingAs(int $userId): self
    {
        $guard = 'session';
        $hash = sha1(SessionGuard::class);

        $this->withSession([
            "login_{$guard}_{$hash}" => $userId,
        ]);

        return $this;
    }

    public function assertChangesEqualsFixture(
        string $table,
        string $fixture,
        Collection $originData,
        bool $exportMode = false
    ): void
    {
        $this->cacheJsonFields($table);

        $changes = $this->getChanges($table, $originData);

        $this->assertEqualsFixture($fixture, $changes, $exportMode);
    }

    public function assertNoChanges(string $table, Collection $originData): void
    {
        $changes = $this->getChanges($table, $originData);

        $this->assertEquals([
            'updated' => [],
            'created' => [],
            'deleted' => []
        ], $changes);
    }

    protected function getChanges(string $table, Collection $originData): array
    {
        $updatedData = $this->getDataSet($table);

        $updatedRecords = [];
        $deletedRecords = [];

        $originData->each(function ($originItem) use (&$updatedData, &$updatedRecords, &$deletedRecords) {
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
            'updated' => $this->prepareChanges($table, $updatedRecords),
            'created' => $this->prepareChanges($table, $updatedData->values()->toArray()),
            'deleted' => $this->prepareChanges($table, $deletedRecords)
        ];
    }

    protected function prepareChanges(string $table, array $changes): array
    {
        $jsonFields = Arr::get(self::$jsonFields, $table);

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

    protected function getDataSet(string $table, string $orderField = 'id'): Collection
    {
        return DB::table($table)
            ->orderBy($orderField)
            ->get()
            ->map(function ($record) {
                return (array) $record;
            });
    }

    protected function cacheJsonFields(string $table): void
    {
        if (!isset(self::$jsonFields[$table])) {
            self::$jsonFields[$table] = [];

            $fields = Schema::getColumnListing($table);

            foreach ($fields as $field) {
                $type = Schema::getColumnType($table, $field);

                if (($type === 'json') || ($type === 'jsonb')) {
                    self::$jsonFields[$table][] = $field;
                }
            }
        }
    }
}
