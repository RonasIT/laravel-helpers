<?php

namespace RonasIT\Support\Traits;

use Illuminate\Auth\SessionGuard;

trait AuthTestTrait
{
    public function actingViaSession(int $userId): self
    {
        $guard = 'session';
        $hash = sha1(SessionGuard::class);

        $this->withSession([
            "login_{$guard}_{$hash}" => $userId,
        ]);

        return $this;
    }
}
