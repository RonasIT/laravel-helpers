<?php

namespace RonasIT\Support\Contracts;

interface DBTypeResolverContract
{
    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function ranges(): array;

    public function hasType(string $type): bool;

    public function isNumeric(string $type): bool;

    public function isString(string $type): bool;
}
