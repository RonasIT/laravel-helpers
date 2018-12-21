## To migrate your project to 1.1: 

#### EntityControlTrait

All relations variables moved form method arguments to `withRelations` method.

Methods: withRelations, withTrashed, onlyTrashed return `$this` to make call chains.

Before: 
```php
$repository->withTrashed();

$repository->firstWithRelations(
    ['id' => 1], 
    ['some_relation']
);
```

After:
```php
$repository
    ->withRelations($relation)
    ->withTrashed()
    ->first();
```

Method `updateMany`: now is used for updating multiple entities in database.

Method `update`: now is used for updated first found entity in database. 
Triggers eloquent functional such as casts, mutator, accessor. 
If first argument is not an array, then it will be used as primary key.

Before: 
```php

$repository->update([
    'id' => 1
], [
    'name' => 'newName'
]);

$repository->update([
    'name' => 'Jon'
], [
    'name' => 'Jonatan'
]);
```

After:
```php
$repository->update([
    'id' => 1
], [
    'name' => 'newName'
]);

$repository->update(1, [
    'name' => 'newName'
]);

$repository->updateMany([
    'name' => 'Jon'
], [
    'name' => 'Jonatan'
]);
```

Method firstOrCreate: now is accepting 2 parameters.

Before: 
```php
$repository->firstOrCreate([
    'name' = 'Jon'
]);
```

After: 
```php
$repository->firstOrCreate([
    'id' => 5
], [
    'name' => 'Jon'
]);
```

Methods `find` and `findBy` don't contains relations in arguments.  
Before:
```php
$this->find(1, ['relation']);
``` 
After: 
```php
$this->withRelations(['relation'])->find(1);
```

Before:
```php
$this->findBy('email', 'test@test.com', ['relation']);
``` 
After: 
```php
$this->withRelations(['relation'])->findBy('email', 'test@test.com');
```

#### FilesUploadTrait 

This class is now used to upload files, all other classes is now @deprecated.

#### FixturesTrait

jsonExport now have jsonExport($fixture, $data) call. 

Before: 
```php
$this->exportJson($data, $fixture);
```

After:
```php
$this->exportJson($fixture, $data)
```

#### SearchTrait

Added new methods: filterMoreOrEqualThan and filterLessOrEqualThan.

getSearchResults now will always return all responses in one format
```json
{
    "current_page": 1,
    "data": [...],
    "first_page_url": "https:\/\/localhost\/\/entities?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "https:\/\/localhost\/\/entities?page=1",
    "next_page_url": null,
    "path": "https:\/\/localhost\/\/entities",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
}
```

If you will send flag `all` then you will get all entities in field `data` and field `per_page` will equals to field `total`

#### Others

`UndemandingAuthorizationMiddleware` will be deprecated if you want to upgrade jwt to 1.0 version. Use `'check'` instead.