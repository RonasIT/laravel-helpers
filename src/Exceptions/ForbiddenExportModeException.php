<?php

namespace RonasIT\Support\Exceptions;

class ForbiddenExportModeException extends EntityCreateException
{
    public function __construct()
    {
        parent::__construct(preg_replace('/[ ]+/mu', ' ',
            'Looks like you forget to remove exportJson. If it is your local environment add 
                FAIL_EXPORT_JSON=false to .env.testing.
                If it is dev.testing environment then remove it.'
        ));
    }
}
