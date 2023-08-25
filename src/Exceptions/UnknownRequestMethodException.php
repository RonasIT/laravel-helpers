<?php

namespace RonasIT\Support\Exceptions;

class UnknownRequestMethodException extends EntityCreateException
{
    public function __construct(string $method)
    {
        parent::__construct("Unknown request method '{$method}'");
    }
}
