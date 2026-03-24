<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Support\UncountableWords;

class PluralizationTest extends TestCase
{
    public static function getUncountableWordsData(): array
    {
        return array_map(
            callback: static fn (string $word): array => [$word],
            array: UncountableWords::LIST,
        );
    }

    #[DataProvider('getUncountableWordsData')]
    public function testUncountableWordStaysUnchanged(string $word): void
    {
        $this->assertEquals($word, Str::plural($word));
        $this->assertEquals(Str::upper($word), Str::plural(Str::upper($word)));
        $this->assertEquals(Str::ucfirst($word), Str::plural(Str::ucfirst($word)));
    }

    public static function getRegularWordsData(): array
    {
        return [
            ['user', 'users'],
            ['category', 'categories'],
            ['post', 'posts'],
        ];
    }

    #[DataProvider('getRegularWordsData')]
    public function testRegularWordsStillPluralize(string $word, string $expected): void
    {
        $this->assertEquals($expected, Str::plural($word));
    }

    public function testUncountableWordsAreRegistered(): void
    {
        $this->assertEmpty(array_diff(UncountableWords::LIST, Pluralizer::$uncountable));
    }
}
