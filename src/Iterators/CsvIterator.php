<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 25.11.16
 * Time: 10:05
 */

namespace RonasIT\Support\Iterators;

use Iterator;

class CsvIterator implements Iterator {
    protected $currentCsvLine = [];
    protected $currentRow = 0;
    protected $file;
    protected $filePath;

    public function __construct($filePath) {
        $this->file = fopen($filePath, 'r');
        $this->filePath = $filePath;
    }

    function rewind() {
        fclose($this->file);

        $this->file = fopen($this->filePath, 'r');

        $this->currentCsvLine = fgetcsv($this->file);
        $this->currentRow = 0;
    }

    function current() {
        return $this->currentCsvLine;
    }

    function key() {
        return $this->currentRow;
    }

    function next() {
        $this->currentCsvLine = fgetcsv($this->file);
        $this->currentRow++;
    }

    function valid() {
        return $this->currentCsvLine !== false;
    }
}