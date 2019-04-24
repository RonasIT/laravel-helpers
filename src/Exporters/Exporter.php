<?php

namespace RonasIT\Support\Exporters;

use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use RonasIT\Support\Interfaces\ExporterInterface;

abstract class Exporter implements FromQuery, WithHeadings, WithMapping, ExporterInterface
{
    use Exportable;

    protected $query;
    protected $fileName;
    protected $type = 'csv';

    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    public function query()
    {
        return $this->query;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @param $type string should be one of presented here https://docs.laravel-excel.com/3.0/exports/export-formats.html
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function export()
    {
        $filename = $this->getFileName();

        $this->query->select($this->getFields());

        $this->store($filename, null, ucfirst($this->type));

        return Storage::path($filename);
    }

    private function getFileName()
    {
        $this->fileName = empty($this->fileName) ? uniqid() : $this->fileName;

        return $this->fileName . '.' . $this->type;
    }

    public function headings(): array
    {
        return $this->getFields();
    }

    public function map($row): array
    {
        return $row->toArray();
    }

    abstract public function getFields();
}
