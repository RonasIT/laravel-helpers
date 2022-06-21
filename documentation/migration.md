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
```

New syntax:

```php
$userRepository->withRelations(['role'])->first(['id' => 1]);
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

### FilesUploadTrait 

This class is now used to upload files, all other classes is now @deprecated.

### FixturesTrait

jsonExport now have jsonExport($fixture, $data) call. 

Before:

```php
$this->exportJson($data, $fixture);
```

After:

```php
$this->exportJson($fixture, $data)
```

### SearchTrait

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

If you'll send flag `all` then you will get all entities in field `data` and field `per_page` will equal to field `total`

### Others

`UndemandingAuthorizationMiddleware` will be deprecated if you want to upgrade jwt to 1.0 version. Use `'check'` instead.

## 2.0.0

### EntityControlTrait

All implemented methods now return model classes/collections instead of arrays

### HttpRequestService

#### Removed methods

The next methods had been removed:
- `sendGet`
- `sendPut`
- `sendPost`
- `sendPatch`
- `sendDelete`
- `parseJsonResponse`

#### New methods

The next methods had been implemented:
- `get(string $url, array $data = [], array $headers = []): self`
- `put(string $url, array $data, array $headers = []): self`
- `post(string $url, array $data, array $headers = []): self`
- `delete(string $url, array $headers = []): self`
- `patch(string $url, array $data, array $headers = []): self`
- `getResponse(): ResponseInterface`
- `json(): array`

Old syntax:

```php
$response = $httpRequestService->get($url);

$data = $httpRequestService->parseJsonResponse($response);
```

New syntax:

```php
$data = $httpRequestService->get($url)->json();
```

## 2.0.8

### FilesUploadTrait

#### saveFile($fileName, $content): string

- now return generated file name instead of url/path
- `$returnUrl` third argument had been removed