<?php

namespace RonasIT\Support\Tests\Support\Request;

use RonasIT\Support\Http\BaseRequest;

class ValidateResolvedTestRequest extends BaseRequest
{
    public bool $initCalled = false;
    public bool $beforeCalled = false;

    public function rules(): array
    {
        return [];
    }

    protected function init(): void
    {
        $this->initCalled = true;
    }

    protected function before(): array
    {
        $this->beforeCalled = true;

        return [];
    }
}
