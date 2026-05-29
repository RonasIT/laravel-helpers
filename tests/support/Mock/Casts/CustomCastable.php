<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;

readonly class CustomCastable implements Castable, JsonSerializable
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
            public function get(Model $model, string $key, mixed $value, array $attributes): CustomCastable
            {
                return new CustomCastable(
                    lineOne: $attributes['address_line_one'],
                    lineTwo: $attributes['address_line_two'],
                );
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): array
            {
                return [
                    'address_line_one' => $value->lineOne,
                    'address_line_two' => $value->lineTwo,
                ];
            }
        };
    }
}
