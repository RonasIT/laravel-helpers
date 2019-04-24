<?php

namespace RonasIT\Support\Interfaces;

interface ExporterInterface
{
    /**
     * Set fields to export
     *
     * @return array
     */
    public function getFields();

    /**
     * Set name of exported file
     *
     * @param $fileName string
     */
    public function setFileName($fileName);

    /**
     * Set exporting format
     *
     * @param $type string should be one of presented here https://docs.laravel-excel.com/3.0/exports/export-formats.html
     * @return $this
     */
    public function setType($type);
}


