<?php

namespace RonasIT\Support\Tests;

use ErrorException;
use ReflectionProperty;
use RonasIT\Support\Exceptions\IncorrectCSVFileException;
use RonasIT\Support\Iterators\CsvIterator;

class CsvIteratorTest extends HelpersTestCase
{
    protected CsvIterator $csvIteratorClass;
    protected ReflectionProperty $columnsProperty;
    protected ReflectionProperty $filePathProperty;

    public function setUp(): void
    {
        parent::setUp();

        $this->csvIteratorClass = new CsvIterator($this->getFixturePath('addresses.csv'));

        $this->columnsProperty = new ReflectionProperty(CsvIterator::class, 'columns');
        $this->columnsProperty->setAccessible(true);

        $this->filePathProperty = new ReflectionProperty(CsvIterator::class, 'filePath');
        $this->filePathProperty->setAccessible(true);
    }

    public function testOpenNotExistsFile()
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('fopen(not_exists_file.csv): Failed to open stream: No such file or directory');

        new CsvIterator('not_exists_file.csv');
    }

    public function testParseColumns()
    {
        $header = $this->getJsonFixture('header');

        $this->csvIteratorClass->parseColumns($header);

        $actualHeader = $this->columnsProperty->getValue($this->csvIteratorClass);

        $this->assertEquals($header, $actualHeader);
    }

    public function testSetColumns()
    {
        $header = $this->getJsonFixture('header');

        $this->csvIteratorClass->setColumns($header);

        $actualHeader = $this->columnsProperty->getValue($this->csvIteratorClass);

        $this->assertEquals($header, $actualHeader);
    }

    public function testCurrent()
    {
        $currentLine = $this->csvIteratorClass->current();

        $this->assertEqualsFixture('current_line', $currentLine);
    }

    public function testKey()
    {
        $rowKey = $this->csvIteratorClass->key();

        $this->assertEquals(0, $rowKey);
    }

    public function testNext()
    {
        $this->csvIteratorClass->next();

        $rowKey = $this->csvIteratorClass->key();
        $currentLine = $this->csvIteratorClass->current();

        $this->assertEquals(1, $rowKey);
        $this->assertEqualsFixture('current_after_next_line', $currentLine);
    }

    public function testRewind()
    {
        $this->csvIteratorClass->next();
        $this->csvIteratorClass->rewind();

        $rowKey = $this->csvIteratorClass->key();
        $currentLine = $this->csvIteratorClass->current();

        $this->assertEquals(1, $rowKey);
        $this->assertEqualsFixture('current_after_next_line', $currentLine);
    }

    public function testGeneratorWithoutSettingColumnHeaders()
    {
        $result = [];
        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $row) {
            $result[] = $row;
        }

        $rowKey = $this->csvIteratorClass->key();

        $this->assertEquals(7, $rowKey);
        $this->assertEqualsFixture('all_data', $result);
    }

    public function testGeneratorWithSettingColumnHeaders()
    {
        $result = [];
        $header = $this->getJsonFixture('header');

        $this->filePathProperty->setValue($this->csvIteratorClass, $this->getFixturePath('addresses_without_header.csv'));
        $this->csvIteratorClass->setColumns($header);

        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $row) {
            $result[] = $row;
        }

        $rowKey = $this->csvIteratorClass->key();

        $this->assertEquals(6, $rowKey);
        $this->assertEqualsFixture('all_data', $result);
    }

    public function testGeneratorWithHeadersInvalidCount()
    {
        $this->expectException(IncorrectCSVFileException::class);
        $this->expectExceptionMessage('Incorrect CSV file');

        $header = $this->getJsonFixture('header_invalid_count');

        $this->csvIteratorClass->setColumns($header);

        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $row) {
        }
    }

    public function testValid()
    {
        $this->assertTrue($this->csvIteratorClass->valid());

        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $row) {
        }

        $this->assertFalse($this->csvIteratorClass->valid());
    }
}
