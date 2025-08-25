[<< Helpers][1]
[Traits >>][2]

# Versioning

The package provides a set of tools for implementing versioning in Laravel applications. These include:

- macros for the Route facade,
- middleware for version handling,
- helpers for working with versions,
- configuration keys for customization.

## Route Macros

The macros provide a convenient way to manage API versioning in Laravel routes.

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

Allows to define the only allowed API version for routes.

```
Route::version(VersionEnum::v1)->get(...)
Route::version(VersionEnum::v1)->group(...)
```

## Middleware

### VersioningMiddleware

The `VersioningMiddleware` is responsible for validating the requested API version and ensuring that requests are properly
routed without version-specific parameters.

If the requested version is listed as disabled in the configuration, the application responds with HTTP_UPGRADE_STATUS.
Blocked versions are managed through the `disabled_api_versions` parameter in the application configuration file 
located at `/app/config/app.php`.

Example:
```
'disabled_api_versions' => [
    VersionEnum::v1->value,
    VersionEnum::v3->value,
] 
```
After performing the version check, the middleware removes the API version parameter from the route before the request 
is passed to the controller. 

This guarantees that controllers operate on a clean request and that version handling remains centralized within middleware.

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

[<< Helpers][1]
[Traits >>][2]

[1]:helpers.md
[2]:traits.md