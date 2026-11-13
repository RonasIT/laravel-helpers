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

## 3.9.0

### Guzzle 8 support

The package now allows both `guzzlehttp/guzzle` `^7.15.2` and `^8.0`. Nothing changes for a project that stays on Guzzle 7, except that a locked Guzzle version below 7.15.2 has to be updated together with the package:

```sh
composer update ronasit/laravel-helpers guzzlehttp/guzzle -W
```

#### Staying on Guzzle 7

Laravel 13 allows Guzzle 8, so a project without its own Guzzle constraint may get Guzzle 8 on `composer update`. Pin the major version in the project until its code is ready:

```sh
composer require guzzlehttp/guzzle:^7.15.2
```

Projects using `ronasit/laravel-telescope-extension` stay on Guzzle 7 anyway: its `muhammadhuzaifa/telescope-guzzle-watcher` dependency requires Guzzle `^7.9`.

#### Moving a project to Guzzle 8

`HttpRequestService` sends the same request options on both versions, so tests asserting the options of a mocked `Client` need no changes. Check the project code for the following Guzzle 8 changes:

- `getResponse()` and `hasResponse()` exist only on `ResponseException` and its subclasses (`ClientException`, `ServerException`), not on the base `RequestException`. Use `HttpRequestService::getResponse()` or catch `ResponseException`;
- `RequestException` no longer accepts a response constructor argument, tests creating it with a response should create `ClientException` or `ServerException` instead;
- network errors without a response are thrown as `NetworkException`, which is not a `RequestException`. Catch `Psr\Http\Client\NetworkExceptionInterface` to handle them on both versions;
- header values passed directly to `Client` must be strings, `HttpRequestService` casts them itself;
- `form_params` values must be scalars, nulls or nested arrays, objects are rejected;
- the default connect timeout of `Client` is 60 seconds instead of 300. `HttpRequestService` keeps passing `connect_timeout => 0` by default, which means the libcurl default of 300 seconds on Guzzle 7 and no connect timeout on Guzzle 8. Call `setConnectTimeout()` to limit it;
- TLS 1.2 is the minimum protocol version by default.

### HttpRequestService

#### getResponse(): ?ResponseInterface

The method returns `null` when the last request failed without a response, it does not keep the response of the previous request anymore. The `json()` method throws `InvalidJSONFormatException` in this case.
