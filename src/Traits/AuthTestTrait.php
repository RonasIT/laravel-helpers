<?php

namespace RonasIT\Support\Traits;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;

trait AuthTestTrait
{
    public function actingViaSession(int $userId, string $guard = 'session'): self
    {
        $hash = sha1(SessionGuard::class);

        return $this->withSession([
            "login_{$guard}_{$hash}" => $userId,
        ]);
    }

    public function actingAs(Authenticatable $user): self
    {
        return parent::actingAs(clone $user);
    }
}
