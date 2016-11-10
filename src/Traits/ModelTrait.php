<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 21.09.16
 * Time: 12:52
 */

namespace RonasIT\Support\Traits;

use Schema;

trait ModelTrait
{
    public static function getFields() {
        $fillable = (new static)->getFillable();

        $fillable[] = 'id';

        return $fillable;
    }

    public function getAllFieldsWithTable() {
        $fields = Schema::getColumnListing($this->getTable());

        return array_map(function ($field) {
            return "{$this->getTable()}.{$field}";
        }, $fields);
    }
}