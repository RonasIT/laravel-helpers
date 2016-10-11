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
    public function fields() {
        return $this->fillable;
    }

    public static function getFields() {
        return (new static)->fields();
    }
}