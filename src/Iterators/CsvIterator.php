<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 25.11.16
 * Time: 10:05
 */

namespace RonasIT\Support\Iterators;

use Iterator;

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