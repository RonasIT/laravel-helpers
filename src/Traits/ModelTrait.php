<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 21.09.16
 * Time: 12:52
 */

namespace RonasIT\Support\Traits;

trait ModelTrait
{
    public static function getFields() {
        $fillable = (new static)->getFillable();

        $fillable[] = 'id';

        return $fillable;
    }
}