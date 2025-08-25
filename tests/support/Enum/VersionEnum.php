<?php

namespace RonasIT\Support\Tests\Support\Enum;

use RonasIT\Support\Contracts\VersionEnumContract;
use RonasIT\Support\Traits\EnumTrait;

enum VersionEnum: string implements VersionEnumContract
{
    use EnumTrait;

    case V1 = '1';
    case V2 = '2';
    case V3 = '3';
}
