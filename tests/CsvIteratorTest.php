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

    public function setUp(): void
    {
        parent::setUp();

        $this->csvIteratorClass = new CsvIterator($this->getFixturePath('addresses.csv'));

        $this->columnsProperty = new ReflectionProperty(CsvIterator::class, 'columns');
        $this->columnsProperty->setAccessible(true);
    }

    public function testOpenNotExistsFile()
    {
        $this->expectException(ErrorException::class);

        new CsvIterator('not_exists_file.csv');

        $this->expectExceptionMessage('fopen(not_exists_file.csv): failed to open stream: No such file or directory');
    }

    public function testParseColumns()
    {
        $header = $this->getJsonFixture('header.json');

        $this->csvIteratorClass->parseColumns($header);

        $actualHeader = $this->columnsProperty->getValue($this->csvIteratorClass);

        $this->assertEquals($header, $actualHeader);
    }

    public function testCurrent()
    {
        $currentLine = $this->csvIteratorClass->current();

        $this->assertEqualsFixture('current_line.json', $currentLine);
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
        $this->assertEqualsFixture('current_after_next_line.json', $currentLine);
    }

    public function testRewind()
    {
        $this->csvIteratorClass->next();
        $this->csvIteratorClass->rewind();

        $rowKey = $this->csvIteratorClass->key();
        $currentLine = $this->csvIteratorClass->current();

        $this->assertEquals(0, $rowKey);
        $this->assertEqualsFixture('current_after_next_line.json', $currentLine);
    }

    public function testGenerator()
    {
        $result = [];
        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $columns) {
            $result[] = $columns;
        }

        $rowKey = $this->csvIteratorClass->key();

        $this->assertEquals(6, $rowKey);
        $this->assertEqualsFixture('all_data.json', $result);
    }

    public function testGeneratorWithHeader()
    {
        $result = [];
        $header = $this->getJsonFixture('header.json');

        $this->csvIteratorClass->parseColumns($header);

        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $row) {
            $result[] = $row;
        }

        $rowKey = $this->csvIteratorClass->key();

        $this->assertEquals(6, $rowKey);
        $this->assertEqualsFixture('all_data_with_header.json', $result);
    }

    public function testGeneratorWithHeadersInvalidCount()
    {
        $this->expectException(IncorrectCSVFileException::class);

        $header = $this->getJsonFixture('header_invalid_count.json');

        $this->csvIteratorClass->parseColumns($header);

        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $row) {
        }

        $this->expectExceptionMessage('Incorrect CSV file');
    }

    public function testValid()
    {
        $this->assertTrue($this->csvIteratorClass->valid());

        $generator = $this->csvIteratorClass->getGenerator();

        foreach ($generator as $columns) {}

        $this->assertFalse($this->csvIteratorClass->valid());
    }
}
