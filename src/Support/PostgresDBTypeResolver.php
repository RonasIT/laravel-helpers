<?php

namespace RonasIT\Support\Support;

use RonasIT\Support\Contracts\DBTypeResolverContract;

class PostgresDBTypeResolver implements DBTypeResolverContract
{
    public static function ranges(): array
    {
        return [
            'smallint'    => [-32768, 32767],
            'integer'     => [-2147483648, 2147483647],
            'bigint'      => [PHP_INT_MIN, PHP_INT_MAX],
            'smallserial' => [1, 32767],
            'serial'      => [1, 2147483647],
            'bigserial'   => [1, PHP_INT_MAX],
            'varchar'     => [0, 255],
            'text'        => [0, PHP_INT_MAX],
        ];
    }

    public function isNumeric(string $type): bool
    {
        return in_array($type, $this->numericTypes(), true);
    }

    public function isString(string $type): bool
    {
        return in_array($type, $this->stringTypes(), true);
    }

    private function numericTypes(): array
    {
        return ['smallint', 'integer', 'bigint', 'smallserial', 'serial', 'bigserial'];
    }

    private function stringTypes(): array
    {
        return ['varchar', 'text'];
    }
}
