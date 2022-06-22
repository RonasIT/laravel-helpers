# Migration guide

## 1.1

### EntityControlTrait

#### Call chains

Next methods return self to allow to use call chain: `withRelations`, `withTrashed`, `onlyTrashed`.

Old syntax:

```php
$repository->withTrashed();

$repository->first(['id' => 1]);
```

New syntax:

```php
$repository->withTrashed()->first(['id' => 1]);
```

#### withRelations method

Attaching relations moved from the methods arguments to separate `withRelations` method.

Old syntax:

```php
$userRepository->firstWithRelations(['id' => 1], ['role']);
$userRepository->find(1, ['role']);
$userRepository->findBy('id', 1, ['role']);
```

New syntax:

```php
$userRepository->withRelations(['role'])->first(['id' => 1]);
$userRepository->withRelations(['role'])->find(1);
$userRepository->withRelations(['role'])->findBy('id', 1);
```

#### update and updateMany method

New `updateMany` method should be used for updating multiple entities in database.

```php
$this->userRepository->updateMany(['is_active' => false], ['role_id' => ROLE::ARCHIVE_USER]);
```

The `update` method now using for updated first found entity in database.

```php
$this->userRepository->update(['id' => 1], ['is_active' => false]);
```

The first argument will be interpreted as a primary key condition if it has non array type.

```php
$this->userRepository->update(1, ['is_active' => false]);
```

#### firstOrCreate method

Now is accepting 2 parameters.

Old syntax:

```php
$repository->firstOrCreate(['name' = 'Jon', 'email' => 'john@mail.com']);
```

New syntax:

```php
$repository->firstOrCreate(['email' => 'john@mail.com'], ['name' = 'Jon']);
```

### FilesUploadTrait 

This class is now used to upload files, all other classes is now @deprecated.

### FixturesTrait

#### exportJson

Changed arguments order

Old syntax:

```php
$this->exportJson($data, $fixture);
```

New syntax:

```php
$this->exportJson($fixture, $data);
```

### SearchTrait

#### filterMoreOrEqualThan and filterLessOrEqualThan

Filtering records by more/less or equals to the filter value.

#### All flag

After applying `all` filter - results will be return with the pagination wrapper.

```json
{
    "current_page": 1,
    "data": [],
    "first_page_url": "https:\/\/localhost\/\/entities?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "https:\/\/localhost\/\/entities?page=1",
    "next_page_url": null,
    "path": "https:\/\/localhost\/\/entities",
    "per_page": 921,
    "prev_page_url": null,
    "to": 1,
    "total": 921
}
```

`per_page` field will be equal to the `total` field in this case.

### Others

`UndemandingAuthorizationMiddleware` will be deprecated if you want to upgrade jwt to 1.0 version. Use `'check'` instead.
