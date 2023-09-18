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

    /**
     * @deprecated use setColumns instead
     */
    public function parseColumns($columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function setColumns($columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function rewind(): void
    {
        fclose($this->file);

        $this->file = fopen($this->filePath, 'r');
        $this->currentRow = 0;

        $this->next();
    }

    public function current(): array
    {
        if (empty($this->columns)) {
            return $this->currentCsvLine;
        }

        if (count($this->columns) !== count($this->currentCsvLine)) {
            throw new IncorrectCSVFileException('Incorrect CSV file');
        }

        return array_combine($this->columns, $this->currentCsvLine);
    }

    public function getGenerator(): Generator
    {
        $this->rewind();

        if (empty($this->columns)) {
            $this->columns = $this->currentCsvLine;

            $this->next();
        }

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
