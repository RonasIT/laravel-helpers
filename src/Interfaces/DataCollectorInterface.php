<?php

namespace RonasIT\Support\Interfaces;

/**
 * @deprecated Use SwaggerDriverInterface instead https://github.com/RonasIT/laravel-swagger/blob/master/src/Interfaces/SwaggerDriverInterface.php
 */
interface DataCollectorInterface
{
    /**
     * Save temporary data
     *
     * @param array $data
     */
    public function saveTmpData($data);

    /**
     * Get temporary data
     */
    public function getTmpData();

    /**
     * Save production data
     */
    public function saveData();

    /**
     * Get production documentation
     */
    public function getDocumentation();
}


