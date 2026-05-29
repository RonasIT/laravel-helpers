<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

readonly class AddressCastable implements Castable, JsonSerializable
{
    public function __construct(
        public string $lineOne,
        public string $lineTwo,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'line_one' => $this->lineOne,
            'line_two' => $this->lineTwo,
        ];
    }

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes {
            public function get(Model $model, string $key, mixed $value, array $attributes): AddressCastable
            {
                $data = json_decode($value, true) ?? [];

                return new AddressCastable(
                    lineOne: $data['line_one'] ?? '',
                    lineTwo: $data['line_two'] ?? '',
                );
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): string
            {
                return json_encode([
                    'line_one' => $value->lineOne,
                    'line_two' => $value->lineTwo,
                ]);
            }
        };
    }
}
