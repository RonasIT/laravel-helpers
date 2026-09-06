<?php

namespace RonasIT\Support\Tests\Support\Mock\Casts;

use Stringable;

/**
 * Cast that can be declared as an object instance.
 *
 * Laravel versions with the `Stringable` guard in `ensureCastsAreStringValues()` convert such
 * an instance into its class name during model initialization, older ones keep the object in
 * `getCasts()` as is. Both cases have to be resolved to the same cast type.
 */
class StringableJSONCast extends JSONCustomCast implements Stringable
{
    public function __toString(): string
    {
        return static::class;
    }
}
