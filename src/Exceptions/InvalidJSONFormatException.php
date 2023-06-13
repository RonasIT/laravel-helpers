<?php

namespace RonasIT\Support\Exceptions;

use Exception;

class InvalidJSONFormatException extends Exception
{
    public function __construct(string $response)
    {
        parent::__construct("Response contains invalid JSON.\nReceived response: '{$response}'");
    }
}
