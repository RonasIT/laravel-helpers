<?php

namespace RonasIT\Support\Exceptions;

use Illuminate\Contracts\Container\BindingResolutionException;

class BindingVersionEnumException extends BindingResolutionException
{
    public function __construct()
    {
        parent::__construct(
            "The VersionEnumContract is not bound in the container.\n"
                . 'Please ensure it is registered correctly '
                . "https://github.com/RonasIT/laravel-helpers/blob/master/documentation/versioning.md#step-2\n"
                . 'More info here https://github.com/RonasIT/laravel-helpers/blob/master/documentation/versioning.md'
        );
    }
}
