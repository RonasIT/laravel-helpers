<?php

namespace RonasIT\Support\Tests\Support\Request;

use RonasIT\Support\Http\BaseRequest;

class ValidateResolvedTestRequest extends BaseRequest
{
    public array $calls = [];
    public array $validationRules = [];
    public array $beforeAuthorizationHandlers = [];

    public function rules(): array
    {
        return $this->validationRules;
    }

    protected function init(): void
    {
        $this->calls[] = 'init';
    }

    protected function beforeAuthorization(): array
    {
        $this->calls[] = 'beforeAuthorization';

        return $this->beforeAuthorizationHandlers;
    }
}
