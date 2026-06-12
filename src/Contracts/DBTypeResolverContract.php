<?php

namespace RonasIT\Support\Contracts;

use RonasIT\Support\Enums\DBTypeCategoryEnum;

interface DBTypeResolverContract
{
    /**
     * @return array{0: int|float|string, 1: int|float|string}
     */
    public function getRange(string $type): array;

    public function hasType(string $type): bool;

    public function getTypeCategory(string $type): ?DBTypeCategoryEnum;
}
