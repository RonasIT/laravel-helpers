<?php

namespace RonasIT\Support\Contracts;

interface VersionEnumContract {
    public static function values(): array;

    public static function toString(string $separator = ','): string;
}