<?php

namespace RonasIT\Support\Contracts;

use RonasIT\Support\Enums\DBTypeCategoryEnum;

interface DBTypeResolverContract
{
    /**
     * @return array<string, array{0: int|float|string, 1: int|float|string}>
     */
    public static function ranges(): array;

    public function hasType(string $type): bool;

    public function isTypeCategory(DBTypeCategoryEnum $category, string $type): bool;
}
