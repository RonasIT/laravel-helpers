<?php

namespace RonasIT\Support\Exporters;

use Illuminate\Support\Arr;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use RonasIT\Support\Interfaces\ExporterInterface;

abstract class Exporter implements FromQuery, WithHeadings, WithMapping, ExporterInterface
{
    use Exportable;

    protected $disk;
    protected $query;
    protected $fileName;
    protected $type = 'csv';

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'local');
    }

    public function setQuery($query): self
    {
        $this->query = $query;

        return $this;
    }

    public function setDisk($disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @param $type string default: csv, should be one of presented here https://docs.laravel-excel.com/3.0/exports/export-formats.html
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function export(): string
    {
        $filename = $this->getFileName();

        $this->store($filename, $this->disk, ucfirst($this->type));

        return Storage::disk($this->disk)->path($filename);
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return is_associative($this->getFields()) ? array_keys($this->getFields()) : $this->getFields();
    }

    public function map($row): array
    {
        return array_map(function ($fieldName) use ($row) {
            return Arr::get($row, $fieldName);
        }, $this->getFields());
    }

    abstract public function getFields(): array;

    protected function getFileName(): string
    {
        $this->fileName = empty($this->fileName) ? uniqid() : $this->fileName;

        return "{$this->fileName}.{$this->type}";
    }
}
