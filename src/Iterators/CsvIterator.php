<?php

namespace RonasIT\Support\Iterators;

use Iterator;
use RonasIT\Support\Exceptions\IncorrectCSVFileException;

class CsvIterator implements Iterator
{
    protected $currentCsvLine = [];
    protected $currentRow = 0;
    protected $file;
    protected $filePath;
    protected $columns = [];

    public function __construct($filePath)
    {
        $this->file = fopen($filePath, 'r');
        $this->filePath = $filePath;
    }

    public function parseColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function rewind()
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

    public function getGenerator()
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

    public function next()
    {
        $this->currentCsvLine = fgetcsv($this->file);
        $this->currentRow++;
    }

    public function valid()
    {
        return $this->currentCsvLine !== false;
    }
}