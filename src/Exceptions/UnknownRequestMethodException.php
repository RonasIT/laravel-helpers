<?php

namespace RonasIT\Support\Exceptions;

use Exception;

class UnknownRequestMethodException extends Exception
{
    public function __construct(string $method)
    {
        parent::__construct("Unknown request method '{$method}'");
    }
}
