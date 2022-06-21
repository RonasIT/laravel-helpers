<?php

namespace RonasIT\Support\Iterators;

use Iterator;
use Generator;
use RonasIT\Support\Exceptions\IncorrectCSVFileException;

class CsvIterator implements Iterator
{
    protected $file;
    protected $filePath;
    protected $columns = [];
    protected $currentRow = 0;
    protected $currentCsvLine = [];

    public function __construct($filePath)
    {
        $this->file = fopen($filePath, 'r');
        $this->filePath = $filePath;
    }

    public function parseColumns($columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function rewind(): void
    {
        fclose($this->file);

        $this->file = fopen($this->filePath, 'r');

        $this->currentCsvLine = fgetcsv($this->file);
        $this->currentRow = 0;
    }

    public function current()
    {
        if (empty($this->columns)) {
            return $this->currentCsvLine;
        }

        if (count($this->columns) !== count($this->currentCsvLine)) {
            throw new IncorrectCSVFileException('Incorrect CSV file');
        }

        return array_associate($this->currentCsvLine, function ($value, $key) {
            return [
                'key' => $this->columns[$key],
                'value' => $value
            ];
        });
    }

    public function getGenerator(): Generator
    {
        $this->rewind();

        while ($this->valid()) {
            $line = $this->current();

            yield $line;

            $this->next();
        }
    }

    public function key()
    {
        return $this->currentRow;
    }

    public function next(): void
    {
        $this->currentCsvLine = fgetcsv($this->file);
        $this->currentRow++;
    }

    public function valid(): bool
    {
        return $this->currentCsvLine !== false;
    }
}
