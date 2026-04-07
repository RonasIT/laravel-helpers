<?php

namespace RonasIT\Support\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use RonasIT\Support\Tests\Support\Mock\Models\TestModel;

class ModelTraitTest extends TestCase
{
    public static function getWasExchangedData(): array
    {
        return [
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => true,
            ],
            [
                'before' => 'old',
                'after' => null,
                'expected' => false,
            ],
            [
                'before' => null,
                'after' => 'new',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getWasExchangedData')]
    public function testWasExchanged(?string $before, ?string $after, bool $expected)
    {
        $model = $this->createModelWithTransition($before, $after);

        $this->assertSame($expected, $model->wasExchanged('name'));
    }

    public static function getWasFilledData(): array
    {
        return [
            [
                'before' => null,
                'after' => 'new',
                'expected' => true,
            ],
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => false,
            ],
            [
                'before' => 'old',
                'after' => null,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getWasFilledData')]
    public function testWasFilled(?string $before, ?string $after, bool $expected)
    {
        $model = $this->createModelWithTransition($before, $after);

        $this->assertSame($expected, $model->wasFilled('name'));
    }

    public static function getWasClearedData(): array
    {
        return [
            [
                'before' => 'old',
                'after' => null,
                'expected' => true,
            ],
            [
                'before' => null,
                'after' => 'new',
                'expected' => false,
            ],
            [
                'before' => 'old',
                'after' => 'new',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('getWasClearedData')]
    public function testWasCleared(?string $before, ?string $after, bool $expected)
    {
        $model = $this->createModelWithTransition($before, $after);

        $this->assertSame($expected, $model->wasCleared('name'));
    }

    public function testNoChange()
    {
        $model = new TestModel();
        $model->forceFill(['name' => 'same']);
        $model->syncOriginal();

        $this->assertFalse($model->wasExchanged('name'));
        $this->assertFalse($model->wasFilled('name'));
        $this->assertFalse($model->wasCleared('name'));
    }

    public function testOrigin()
    {
        $model = $this->createModelWithTransition('old', 'new');

        $this->assertSame('old', $model->origin('name'));
    }

    public function testOriginReturnsNullWhenNoPreviousValue()
    {
        $model = new TestModel();
        $model->forceFill(['name' => 'value']);
        $model->syncOriginal();

        $this->assertNull($model->origin('name'));
    }

    protected function createModelWithTransition(?string $before, ?string $after): TestModel
    {
        $model = new TestModel();
        $model->forceFill(['name' => $before]);
        $model->syncOriginal();
        $model->forceFill(['name' => $after]);
        $model->syncChanges();
        $model->syncOriginal();

        return $model;
    }
}
