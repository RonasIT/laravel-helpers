<?php

namespace RonasIT\Support\Importers;

use Illuminate\Support\Arr;
use RonasIT\Support\Iterators\CsvIterator;
use RonasIT\Support\Exceptions\IncorrectImportFileException;
use RonasIT\Support\Exceptions\IncorrectImportLineException;

class Importer
{
    const DELETED_AT_FIELD = 'deleted_at';

    const ITEMS_TO_CREATE = 'create';
    const ITEMS_TO_UPDATE = 'update';

    const CREATED_REPORTS = 'created';
    const UPDATED_REPORTS = 'updated';

    protected $input;

    protected $service;
    protected $iterator;
    protected $exporter;

    protected $mandatoryFields = [];

    protected $items = [
        self::ITEMS_TO_CREATE => [],
        self::ITEMS_TO_UPDATE => []
    ];

    protected $report = [
        self::UPDATED_REPORTS => 0,
        self::CREATED_REPORTS => 0,
        'errors' => []
    ];

    public function setInput($input)
    {
        $this->input = $input;

        return $this;
    }

    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    public function setExporter($exporter)
    {
        $this->exporter = app($exporter);

        return $this;
    }

    public function import()
    {
        $this->prepare();

        $this->markAllLines();

        $this->resolve();

        return $this->report;
    }

    public function prepare()
    {
        $this->iterator = new CsvIterator($this->input);
    }

    protected function markAllLines()
    {
        $isFilterLine = true;

        foreach ($this->iterator->getGenerator() as $line) {
            if (!$isFilterLine) {
                try {
                    $csvData = $this->prepareLine($line);
                } catch (IncorrectImportLineException $exception) {
                    $this->addError($exception->getMessage());

                    continue;
                }

                $this->markForCreate($csvData);
                $this->markForUpdate($csvData);

                continue;
            }

            $fields = $this->prepareFields($line);

            if (head($fields) == 'id') {
                $isFilterLine = false;

                $this->iterator->parseColumns($fields);

                $this->validateHeader();
            }
        }
    }

    protected function resolve()
    {
        $this->createAllMarked();
        $this->updateAllMarked();
    }

    protected function prepareFields($line)
    {
        return array_map(function ($field) {
            $field = strtolower($field);

            return str_replace('=EF=BB=BF', '', quoted_printable_encode($field));
        }, $line);
    }

    protected function prepareLine($line)
    {
        if (empty($line['id'])) {
            $line['id'] = null;
        }

        if (Arr::has($line, self::DELETED_AT_FIELD) && empty($line[self::DELETED_AT_FIELD])) {
            $line[self::DELETED_AT_FIELD] = null;
        }

        return $line;
    }

    protected function markForCreate($line)
    {
        if (!$this->isValidForCreation($line)) {
            return;
        }

        $this->items[self::ITEMS_TO_CREATE][] = $line;
    }

    protected function isValidForCreation($line)
    {
        if (empty($line['id'])) {
            return true;
        }

        return !$this->service->withTrashed()->exists(['id' => $line['id']]);
    }

    protected function markForUpdate($line)
    {
        if ($this->isValidForUpdating($line)) {
            $this->items[self::ITEMS_TO_UPDATE][$line['id']] = $line;
        }
    }

    protected function isValidForUpdating($line)
    {
        if (empty($line['id']) || in_array($line, $this->items[self::ITEMS_TO_UPDATE])) {
            return false;
        }

        $diff = $this->getDiff($line);

        return !empty($diff);
    }

    protected function addError($error)
    {
        $this->report['errors'][] = $this->formatError($error);
    }

    protected function formatError($error)
    {
        $lineNumber = $this->iterator->key() + 1;

        return "Line {$lineNumber}: {$error}";
    }

    protected function validateDuplicatingOfId($item)
    {
        if (empty($item['id'])) {
            return false;
        }

        return $this->service->withTrashed()->exists([
            'id' => $item['id']
        ]);
    }

    protected function createAllMarked()
    {
        foreach ($this->items[self::ITEMS_TO_CREATE] as $item) {
            $this->service->create($item);

            $this->report[self::CREATED_REPORTS]++;
        }
    }

    protected function updateAllMarked()
    {
        foreach ($this->items[self::ITEMS_TO_UPDATE] as $id => $item) {
            $this->service->update(['id' => $id], $item);

            $this->report[self::UPDATED_REPORTS]++;
        }
    }

    protected function getDiff($item)
    {
        $itemFromDB = $this->service->first(['id' => $item['id']]);

        $exportedLine = $this->exporter->getLine($itemFromDB);
        $importedLine = array_values($this->iterator->current());

        return array_diff($exportedLine, $importedLine);
    }

    protected function validateHeader()
    {
        $line = $this->iterator->current();

        $mandatoryValues = Arr::only($line, $this->mandatoryFields);

        if (count($mandatoryValues) != count($this->mandatoryFields)) {
            $notExistedFields = array_filter($this->mandatoryFields, function ($field) use ($line) {
                return !Arr::has($line, $field);
            });

            if (count($notExistedFields) == 1) {
                $message = 'Mandatory field ' . head($notExistedFields) . ' is not provided in csv file';
            } else {
                $message = 'Mandatory fields ' . implode(', ', $notExistedFields) . ' are not provided in csv file';
            }

            throw new IncorrectImportFileException($message);
        }
    }
}
