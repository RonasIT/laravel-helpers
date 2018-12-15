## To migrate your project to new version: 

#### EntityControlTrait

All relations variables moved form method call to withRelations method.

Methods : withRelations, withTrashed, onlyTrashed return this and using for making chains.

Examples: 
```php
$repository
    ->withRelations($relation)
    ->withTrashed()
    ->first();
```
Before: 
```php
$repository->withTrashed();

$repository->firstWithRelations(
    ['id' => 1], 
    ['some_relation']
);
```

Method updateMany: now is used for updating multiple entities in database.
Method update: now is used for updated first entity in database. Triggers eloquent functional such as casts, mutator, accessor.

Examples: 
```php
$repository->update([
    'id' => 1
], [
    'name' => 'newName'
]);

$repository->updateMany([
    'name' => 'Jon'
], [
    'name' => 'Jonatan'
]);
```
Before: 
```php

$repository->update(1, [
    'name' => 'newName'
]);

$repository->update([
    'name' => 'Jon'
], [
    'name' => 'Jonatan'
]);
```
Method firstOrCreate: now is accepting 2 parameters.

Examples: 
```php
$repository->firstOrCreate([
    'id' => 5
], [
    'name' => 'Jon'
]);
```
Before: 
```php
$repository->firstOrCreate([
    'name' = 'Jon'
]);
```
#### FilesUploadTrait 

This class is now used to upload files, all other classes is now @deprecated.

#### FixturesTrait

jsonExport now have jsonExport($fixture, $data) call. 

Examples:
```php
$this->exportJson($fixture, $data)
```
Before: 
```php
$this->exportJson($data, $fixture);
```
## New features

#### SearchTrait

Added new methods: filterMoreOrEqualThan and filterLessOrEqualThan.