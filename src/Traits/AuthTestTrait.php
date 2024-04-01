<?php

namespace RonasIT\Support\Traits;

use Illuminate\Auth\SessionGuard;

trait AuthTestTrait
{
    public function actingViaSession(int $userId, string $guard = 'session'): self
    {
        $hash = sha1(SessionGuard::class);

        return $this->withSession([
            "login_{$guard}_{$hash}" => $userId,
        ]);
    }
}
