вот что у меня получилось.  Пожалуйста дай рекомендации по моей документации к фичи в пакете


[<< Helpers][1]
[Traits >>][2]

# Versioning

## Route Macros
The macros provide a convenient way to manage API versioning in Laravel routes.

They allow you to specify which versions of the API a route is accessible for.

By using these macros, routes can be easily grouped and protected according to version-specific logic,
ensuring backward compatibility and smooth evolution of the API.

### versionRange
The versionRange macro allows you to define a range of API versions for which a route is accessible.
```
Route::versionRange(VersionEnum::v1, VersionEnum::v2)
```
### versionFrom
This macro helps define the minimum API version required to access a route.
```
Route::versionFrom(VersionEnum::v1)
```
### versionTo
The versionTo macro allows you to define the maximum API version for which a route is accessible.
```
Route::versionTo(VersionEnum::v2)
```
### version
The version macro allows you to define a specific API version for which a route is accessible.
```
Route::version(VersionEnum::v1)
```

## Middlewares

### CheckVersionMiddleware
This middleware verifies the requested API version.
If the version is blocked in the configuration, the application returns HTTP_UPGRADE_STATUS.

To manage blocked versions, configure the `disabled_api_versions` parameter in your application `/app/config/app.php`.

Example:
```
'disabled_api_versions' => [
    VersionEnum::v1->value,
    VersionEnum::v3->value,
] 
```
### ClearVersion
The ClearVersion middleware removes the API version parameter from the route before the request reaches the controller.

Purpose:

* Ensures that controllers receive a "clean" request without version parameters.
* Allows centralized handling of API versions in middleware, simplifying controller logic.
* Reduces the risk of accidental usage of version parameters inside application logic.

Usage: Can be applied to routes where version parameters should be cleared after validation or handling.

## VersionEnumContract
To manage application versions, an enumeration class must be defined to represent the available version variants.
This enumeration class is required to implement the VersionEnumContract interface.

## Support Version class

The Version class provides utility methods for working with API versions in a Laravel application.

It allows retrieving the current version from the request route and performing comparisons with specific version constraints.

Methods include checking for exact version matches, verifying if the current version falls within a range,
and checking greater-than-or-equal and less-than-or-equal conditions.

This class is typically used to implement version-based logic in controllers or middleware.

## Usage

### Step 1.
Please create an enumeration class to manage your versions.
The enumeration class must implement the VersionEnumContract interface.
```
<?php

namespace App\Enum;

use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Traits\EnumTrait;

enum VersionEnum: string implements VersionEnumContract
{
    use EnumTrait;

    case v1 = '1';

    public static function last(): self
    {
        return self::v1;
    }
}
```
### Step 2.
Next, bind the contract to your enumeration class in the service container.
This enumeration class will define the list of your API versions.

```
    public function register(): void
    {
        $this->app->bind(VersionEnumContract::class, fn () => VersionEnum::class);
    }
```

### Step 3.

Implementation in routes file
```
Route::prefix('v{version}')
    ->middleware([
         CheckVersionMiddleware::class,
         ClearVersion::class,
    ])
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

[<< Helpers][1]
[Traits >>][2]

[1]:helpers.md
[2]:traits.md