<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Contracts\Auth\Authenticatable;

class MockAuthUser implements Authenticatable
{
    public function getAuthIdentifierName(): string
    {
        return 'some_auth_identifier_name';
    }

    public function getAuthIdentifier(): string
    {
        return 'some_auth_identifier';
    }

    public function getAuthPassword(): string
    {
        return 'some_auth_password';
    }

    public function getRememberToken(): string
    {
        return 'some_remember_token';
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName(): string
    {
        return 'some_remember_token_name';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }
}
