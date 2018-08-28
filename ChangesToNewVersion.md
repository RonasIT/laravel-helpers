## To migrate your project to new version: 

#### EntityControlTrait

All relations variables moved form method call to withRelations method.

Methods : withRelations, withTrashed, onlyTrashed return this and using for making chains.
Examples: 
```php
$object->withRelations($relation)->withTrashed()->get();
```
Before: 
```php
$object->withRelations($relation);
$object->withTrashed();
```

Method updateMany: now is used for updating multiple entities in database.
Method update: now is used for updated first entity in database.

Examples: 
```php
$where = ['id' => 1];
$data = ['name' => 'newName'];
$object->update($where, $data);

$where = ['name' => 'Jon'];
$data = ['name' => 'Jonatan'];
$obhect->updateMany($where, $data);
```
Before: 
```php
$where = 1;
$data = ['name' => 'newName'];
$object->update($where, $data);

$where = ['name' => 'Jon'];
$data = ['name' => 'Jonatan'];
$object->update($where, $data);
```
Method firstOrCreate: now is accepting 2 parameters.

Examples: 
```php
$data = ['name' => 'Jon'];
$where = ['id' => 5];
$object->firstOrCreate($where, $data);
```
Before: 
```php
$data = ['name' = 'Jon'];
$object->firstOrCreate($data);
```
#### FilesUploadTrait 
``
This class is now used to upload files, all other clasess is now @depricated.

#### FixturesTrait

jsonExport now have jsonExport($fixture, $data) call. 

Examples:
```php
 exportJson($fixture, $data)
 ```
Before: 
```php
exportJson($data, $fixture);
```
## New features

#### SearchTrait

Added new methods: filterMoreOrEqualThan and filterLessOrEqualThan.