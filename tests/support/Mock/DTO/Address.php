<?php

namespace RonasIT\Support\Tests\Support\Mock\DTO;

readonly class Address
{
    public function __construct(
        public string $country,
        public string $city,
        public string $street,
    ) {
    }
}
