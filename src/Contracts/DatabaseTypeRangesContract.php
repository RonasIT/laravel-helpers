<?php

namespace RonasIT\Support\Contracts;

interface DatabaseTypeRangesContract
{
    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function ranges(): array;

    /**
     * @return array<string>
     */
    public static function integerTypes(): array;

    /**
     * @return array<string>
     */
    public static function stringTypes(): array;
}
