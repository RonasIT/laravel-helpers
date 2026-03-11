<?php

namespace RonasIT\Support\Contracts;

interface DatabaseTypeRangesContract
{
    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function ranges(): array;
}
