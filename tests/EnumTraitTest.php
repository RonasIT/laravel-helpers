<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Enum\VersionEnum;

class EnumTraitTest extends TestCase
{
    public function testValues()
    {
        $result = VersionEnum::values();

        $this->assertEquals(['1', '2', '3'], $result);
    }

    public static function getToStringData(): array
    {
        return [
            [
                'separator' => ',',
                'expected' => '1,2,3',
            ],
            [
                'separator' => ';',
                'expected' => '1;2;3',
            ],
        ];
    }

    #[DataProvider('getToStringData')]
    public function testToString(string $separator, string $expected)
    {
        $result = VersionEnum::toString($separator);

        $this->assertEquals($expected, $result);
    }
}
