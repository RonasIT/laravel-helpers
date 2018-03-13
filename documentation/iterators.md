##Iterators

###CsvIterator

Iterates .csv file.

####__construct($fileName)
- $fileName - string, path to .csv file;

####parseColumns($columns)
Get csv line as associative array.
- $columns - array with csv file's columns;

####getGenerator()
Return iteratable object for foreach.

Example: 
````
>>> $csv = new \RonasIT\Support\Iterators\CsvIterator('/tmp/1.csv')
>>> $csv->parseColumns(['id', 'name']);

>>> foreach ($csv->getGenerator() as $line) {
    var_export($line);
}
=> array (
     'id' => '1',
     'name' => 'first',
   )array (
     'id' => '2',
     'name' => 'second',
   )array (
     'id' => '3',
     'name' => 'third',
   )⏎
````


###DBIterator
Iterates results of database query. It very convenient for imports or exports 
Actually it is just a wrapper of chunk. 

####__construct($query, $itemsPerPage)
- $query - object 
- $itemsPerPage - integer. Size of chunk sample.

Example:
````
>>> $query = \App\Models\Category::with('translation')->orderBy('created_at', 'DESC');
>>> foreach($iterator->getGenerator() as $category) {
   var_export($category);
}
array (
  'id' => 4,
  'created_at' => '2018-01-23 07:20:06',
  'updated_at' => '2018-01-23 07:20:06',
  'deleted_at' => NULL,
  'parent_id' => NULL,
  'translation' => 
  array (
    'id' => 8,
    'locale' => 'en',
    'category_id' => 4,
    'title' => 'Contemporary and modern art',
    'description' => 'Contemporary and modern art',
  ),
)array (
  'id' => 5,
  'created_at' => '2018-01-23 07:20:06',
  'updated_at' => '2018-01-23 07:20:06',
  'deleted_at' => NULL,
  'parent_id' => NULL,
  'translation' => 
  array (
    'id' => 10,
    'locale' => 'en',
    'category_id' => 5,
    'title' => 'Asian Art',
    'description' => '',
  ),
.....
```` 