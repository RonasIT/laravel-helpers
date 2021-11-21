<?php

namespace RonasIT\Support\Interfaces;

interface ExporterInterface
{
    /**
     * Set fields to export
     *
     * Use associative array to use keys as headings
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Set name of exported file
     *
     * @param $fileName string
     */
    public function setFileName(string $fileName);

    /**
     * Set exporting format
     *
     * @param $type string should be one of presented here https://docs.laravel-excel.com/3.0/exports/export-formats.html
     * @return $this
     */
    public function setType(string $type): self;
}


