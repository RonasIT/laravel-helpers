<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class JSONCustomCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        $data = json_decode($value, true) ?? [];

        return [
            'appearance' => [
                'theme' => $data['theme'] ?? null,
            ],
            'locale' => [
                'language' => $data['language'] ?? null,
            ],
            'notifications' => [
                'email' => $data['notifications_email'] ?? false,
                'sms' => $data['notifications_sms'] ?? false,
            ],
        ];
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode([
            'theme' => $value['appearance']['theme'] ?? null,
            'language' => $value['locale']['language'] ?? null,
            'notifications_email' => $value['notifications']['email'] ?? false,
            'notifications_sms' => $value['notifications']['sms'] ?? false,
        ]);
    }
}
