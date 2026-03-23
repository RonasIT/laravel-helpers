<?php

namespace RonasIT\Support\Tests\Support\Request;

use RonasIT\Support\Http\BaseRequest;

class UpdateTestRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'email' => 'string|email|unique_except_of_authorized_user',
            'name' => 'string',
            'address' => 'array',
            'address.*' => 'required|string',
            'meta' => 'array',
            'meta.*.value' => 'required|array',
            'meta.*.description' => 'required|string',
        ];
    }
}
