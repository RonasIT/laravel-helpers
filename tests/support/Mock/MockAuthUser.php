<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Contracts\Auth\Authenticatable;

class MockAuthUser implements Authenticatable
{
    public int $someIntProperty;

    public function getAuthIdentifierName()
    {
        return 'some_auth_identifier_name';
    }

    public function getAuthIdentifier()
    {
        return 'some_auth_identifier';
    }

    public function getAuthPassword()
    {
        return 'some_auth_password';
    }

    public function getRememberToken()
    {
        return 'some_remember_token';
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return 'some_remember_token_name';
    }
}