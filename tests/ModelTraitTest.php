<?php

namespace RonasIT\Support\Tests;

use RonasIT\Support\Tests\Support\Mock\Models\TestModel;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithGuardedFields;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithGuardedWildcard;
use RonasIT\Support\Tests\Support\Mock\Models\TestModelWithoutTimestamps;

class ModelTraitTest extends TestCase
{
    public function testGetFieldsDoesNotContainWildcard()
    {
        $fields = TestModel::getFields();

        $this->assertNotContains('*', $fields);

        $this->assertEquals([
            'id',
            'name',
            'json_field',
            'custom_cast_field',
            'castable_field',
            'created_at',
            'updated_at',
        ], $fields);
    }

    public function testGetFieldsWithGuardedWildcardOnly()
    {
        $fields = TestModelWithGuardedWildcard::getFields();

        $this->assertNotContains('*', $fields);

        $this->assertEquals(['id', 'created_at', 'updated_at'], $fields);
    }

    public function testGetFieldsWithGuardedFields()
    {
        $fields = TestModelWithGuardedFields::getFields();

        $this->assertEquals(['id', 'name', 'secret_field', 'created_at', 'updated_at'], $fields);
    }

    public function testGetFieldsDoesNotContainDuplicates()
    {
        $fields = TestModelWithoutTimestamps::getFields();

        $this->assertEquals(['id', 'name', 'created_at'], $fields);
    }
}
