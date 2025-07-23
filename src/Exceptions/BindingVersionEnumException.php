<?php

namespace RonasIT\Support\Exceptions;

use Illuminate\Contracts\Container\BindingResolutionException;

class BindingVersionEnumException extends BindingResolutionException
{
    public function __construct()
    {
        parent::__construct(
            'The VersionEnumContract is not bound in the container.'
            .' Please ensure it is registered using'
            .' $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);'
        );
    }
}
