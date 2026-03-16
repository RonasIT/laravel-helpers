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
        return [
            ['billing'],
            ['funding'],
            ['ordering'],
        ];
    }

    #[DataProvider('getUncountableWordsData')]
    public function testUncountableWordStaysUnchanged(string $word): void
    {
        $this->assertEquals($word, Str::plural($word));
    }

    #[DataProvider('getUncountableWordsData')]
    public function testUncountableWordIsCaseInsensitive(string $word): void
    {
        $this->assertEquals(Str::upper($word), Str::plural(Str::upper($word)));
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
