[<< Services][1]

## Iterators

### CsvIterator

Iterates `.csv` file.

#### __construct($fileName)

- $fileName - string, path to `.csv` file;

#### parseColumns($columns)

Get csv line as associative array.
- $columns - array with csv file's columns;

#### getGenerator()

Return iterable object.

Example: 

```php
$csv = new \RonasIT\Support\Iterators\CsvIterator('/tmp/1.csv');
$csv->parseColumns(['id', 'name']);

foreach ($csv->getGenerator() as $line) {
    dump($line);
}

//['order_index' => '1', 'name' => 'first']
//['order_index' => '2', 'name' => 'second']
//['order_index' => '3', 'name' => 'third']
````

### DBIterator

Iterate results of the database query via chunk logic. 

#### __construct($query, $itemsPerPage)

- $query - QueryBuilder object;
- $itemsPerPage - integer, chunk size.

```php
$query = \App\Models\Category::orderBy('created_at', 'DESC');

foreach($iterator->getGenerator() as $category) {
   dump($category);
}

//['id' => 1, 'name' => 'first', 'created_at' => '2018-01-23 07:20:06']
//['id' => 5, 'name' => 'second', 'created_at' => '2018-01-23 07:20:06']
```

[<< Services][1]

[1]:services.md
