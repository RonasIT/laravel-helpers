<?php

namespace RonasIT\Support\Tests\Support\Request;

use RonasIT\Support\Http\BaseRequest;

class ValidateResolvedTestRequest extends BaseRequest
{
    public array $calls = [];
    public array $validationRules = [];

    public function rules(): array
    {
        return $this->validationRules;
    }

    protected function init(): void
    {
        $this->calls[] = 'init';
    }

    protected function before(): array
    {
        $this->calls[] = 'before';

        return [];
    }
}
