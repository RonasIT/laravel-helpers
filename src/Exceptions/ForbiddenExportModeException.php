<?php

namespace RonasIT\Support\Exceptions;

class ForbiddenExportModeException extends EntityCreateException
{
    public function __construct()
    {
        $envKey = 'FAIL_EXPORT_JSON';
        $envFile = '.env.testing';

        parent::__construct(
            "Looks like you try to export fixture.\nIf you see this message while "
            . "running tests in the local environment - please set {$envKey}=false to {$envFile}."
        );
    }
}
