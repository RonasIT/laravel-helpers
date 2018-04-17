<?php

namespace App\Support\Importers;

use App\Exceptions\IncorrectImportFileException;
use App\Exceptions\IncorrectImportLineException;
use App\Support\Exporters\LogExporter;
use RonasIT\Support\Iterators\CsvIterator;

class Importer
{
    protected $input;

    protected $service;
    protected $iterator;
    protected $exporter;
    protected $items = [
        'create' => [],
        'update' => []
    ];
    protected $report = [
        'updated' => 0,
        'created' => 0,
        'errors' => []
    ];
    protected $mandatoryFields = [];

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

        $this->exportLogs();
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

        if (array_has($line, 'deleted_at') && empty($line['deleted_at'])) {
            $line['deleted_at'] = null;
        }

        return $line;
    }

    protected function markForCreate($line)
    {
        if (!$this->isValidForCreation($line)) {
            return;
        }

        $this->items['create'][] = $line;
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
            $this->items['update'][$line['id']] = $line;
        }
    }

    protected function isValidForUpdating($line)
    {
        if (empty($line['id']) || in_array($line, $this->items['create'])) {
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
        foreach ($this->items['create'] as $item) {
            $this->service->create($item);

            $this->report['created']++;
        }
    }

    protected function updateAllMarked()
    {
        foreach ($this->items['update'] as $id => $item) {
            $this->service->update(['id' => $id], $item);

            $this->report['updated']++;
        }
    }

    protected function getDiff($item)
    {
        $itemFromDB = $this->service->first(['id' => $item['id']]);

        $exportedLine = $this->exporter->getLine($itemFromDB);
        $importedLine = array_values($this->iterator->current());

        return array_diff($exportedLine, $importedLine);
    }

    public function exportLogs()
    {
        /** @var LogExporter $exporter */
        $exporter = app(LogExporter::class);

        $exporter->setReport($this->report);

        $this->report['log_file'] = $exporter->run();
    }

    protected function validateHeader()
    {
        $line = $this->iterator->current();

        $mandatoryValues = array_only($line, $this->mandatoryFields);

        if (count($mandatoryValues) != count($this->mandatoryFields)) {
            $notExistedFields = array_filter($this->mandatoryFields, function ($field) use ($line) {
                return !array_has($line, $field);
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