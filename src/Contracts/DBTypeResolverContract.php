<?php

namespace RonasIT\Support\Contracts;

use RonasIT\Support\Enums\DBTypeCategoryEnum;

interface DBTypeResolverContract
{
    /**
     * @return array{0: int|float, 1: int|float}
     */
    public function getRange(string $type): array;

    /**
     * @return list<string>
     */
    public function getTypes(): array;

    public function hasType(string $type): bool;

    public function getTypeCategory(string $type): ?DBTypeCategoryEnum;
}
