<?php

namespace RonasIT\Support\Http;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    public static $wrap = null;
}
