##Iterators

###CsvIterator

Iterates throw .csv file.
####__construct($fileName)
- $fileName - string, path to .csv file;

####parseColumns($columns)
Saving columns array to validate file
- $columns - array with csv file's columns;

####getGenerator()
Parse csv file to lines
Return iteratable object.


###DBIterator
Iterates throw db.
####__construct($query, $itemsPerPage)

- $query - object 