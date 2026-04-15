<?php

namespace RonasIT\Support\Exceptions;

use Exception;

class UnsupportedDBDriverException extends Exception
{
    public function __construct(string $driverName)
    {
        parent::__construct('Unsupported database driver: ' . $driverName);
    }
}
