[<< Helpers][1]
[Traits >>][2]

# Versioning

The package provides a set of tools for implementing versioning in Laravel applications. These include:

- macros for the Route facade,
- middleware for version handling,
- helpers for working with versions,
- configuration keys for customization.

## Route Macros

The package provides macros that offer a convenient way to define the range of versions a route supports. 
Macros can be applied both to a single endpoint and to a route group, 
allowing more flexible version constraints across the application.

### versionRange

The `versionRange` macro allows to define a full closed range of API versions allowed for routes.

```
Route::versionRange(VersionEnum::v1, VersionEnum::v2)->get(...)
Route::versionRange(VersionEnum::v1, VersionEnum::v2)->group(...)
```

### versionFrom

Allows to define the minimum API version required to access routes.

```
Route::versionFrom(VersionEnum::v1)->get(...)
Route::versionFrom(VersionEnum::v1)->group(...)
```

### versionTo

Allows to define the maximum API version allowed for routes.

```
Route::versionTo(VersionEnum::v2)->get(...)
Route::versionTo(VersionEnum::v2)->group(...)
```

### version

Adds a version-based prefix to route URLs.

```
Route::version(VersionEnum::v1)->get(...)
Route::version(VersionEnum::v1)->group(...)
```

## VersioningMiddleware

The `VersioningMiddleware` is responsible for two main features:

1. Validating that the requested API version is not in the disabled list
2. Removing the version path param from the list of parameters passed to the controller's method

### Disabled API versions check

To disable the particular API version - need to add it to the `app.disabled_api_versions` config.

If the requested version is listed in the config, the middleware responds with `HTTP_UPGRADE_REQUIRED`.

### Removing version path param

We recommend keeping a common group of routes which will work with any version. The only way to implement it is using the path param with the requested version.

```php
Route::prefix('v{version}')
    ->middleware(VersioningMiddlewre::class)
    ->group(function () {
        Route::versionFrom(VersionEnum::v0_1)->group(function () {
            Route::get('test/{param}', [TestController::class, 'test']);
```

By default, `version` path param will be passed to each controller's method

```php
//TestController
public function test($version, $param)
```

This middleware preventing such behavior and `version` param will be ignoring


```php
//TestController
public function test($param)
```

## VersionEnumContract

Using to indicate your application's versions list. The contract using in a type hint of [the Route macros][3], [VersionHelper][4] and [setAPIVersion][5] test helper.

## Version helper

The `Version` class provides utility methods for working with API versions in any place of the Laravel application.

It allows retrieving the current version from the request route and performing comparisons with specific version constraints.

Methods include checking for exact version matches, verifying if the current version falls within a range,
and checking greater-than-or-equal and less-than-or-equal conditions.

This class is typically used to implement version-based logic in controllers or middleware.

## Testing helpers

The `json` method of the `Testing\TestCase` class has been extended and now will add the version prefix to each test API call, if the API version is set.

### setAPIVersion

Helper using to set the API version as a class field. We suggest setting the actual API version in the `setUp` method of the application's `TestCase` class. In this case - all tests will work with the same actual API version.

When implementing tests for a particular API version, the `setAPIversion` method may be called in the `setUp` method of the specific test class.

### withoutAPIVersion

For some types of tests, the API version may not be used e.g., for webhooks or health status APIs. In these cases the `withoutAPIVersion` helper call will prevent the adding of a version prefix to each API call in tests.

Helper may be called either in a single test or in the `setUp` method of the test case.

## Usage

### Step 1.

Create an Enum structure to store the list of API versions.

Created Enum must implement the [VersionEnumContract][6] interface and use [EnumTrait][7].

```
<?php

namespace App\Enum;

use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Traits\EnumTrait;

enum VersionEnum: string implements VersionEnumContract
{
    use EnumTrait;

    case v1 = '1';
}
```

### Step 2.

Bind the `VersionEnumContract` to the created `VersionEnum` in any service provider.

```
public function register(): void
{
    $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);
}
```

### Step 3.

Wrap all routes to the group of common versions

```
Route::prefix('v{version}')
    ->middleware(VersioningMiddleware::class)
    ->group(function () {
        Route::versionFrom(VersionEnum::v1)->group(function () {
            Route::middleware('auth_group')->group(function () {
                Route::controller(AuthController::class)->group(function () {
                    Route::post('auth/logout', 'logout');
                });
            });
            Route::middleware(['guest_group'])->group(function () {
                Route::controller(AuthController::class)->group(function () {
                    Route::post('login', 'login');
                });
            });
        });
    });
```

### Implement backward compatibility

```
Route::version(VersionEnum::v0_1)->group(function () {
    Route::controller(TestControllerV0_1::class)->group(function () {
        Route::get('tests/{id}', 'get')->whereNumber('id');
    });
});

Route::prefix('v{version}')
    ->middleware(VersioningMiddleware::class)
    ->group(function () {
        Route::versionFrom(VersionEnum::v0_1)->group(function () {
            Route::controller(TestController::class)->group(function () {
                Route::versionFrom(VersionEnum::v0_2)->get('tests/{id}', 'get')->whereNumber('id');
                    
                Route::get('tests', 'tests');   
            });
        });
    });
```


[<< Helpers][1]
[Traits >>][2]

[1]:helpers.md
[2]:traits.md
[3]:../src/HelpersServiceProvider.php#L111
[4]:../src/Support/Version.php
[5]:../src/Testing/TestCase.php#L128
[6]:../src/Contracts/VersionEnumContract.php
[7]:../src/Traits/EnumTrait.php