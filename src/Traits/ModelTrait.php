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
    protected $selectedFields;

    public static function getFields() {
        $fillable = (new static)->getFillable();

        array_unshift($fillable, 'id');

        return $fillable;
    }

    public function getAllFieldsWithTable() {
        $fields = Schema::getColumnListing($this->getTable());

        return array_map(function ($field) {
            return "{$this->getTable()}.{$field}";
        }, $fields);
    }

    /**
     * This method was added, because native laravel's method addSelect
     * overwrites existed select clause
     */
    public function scopeAddFieldsToSelect($query, $fields)
    {
        if (empty($this->selectedFields)) {
            $this->selectedFields = $this->getAllFieldsWithTable();
        }

        $fields = array_merge($this->selectedFields, $fields);

        $query->addSelect($fields);

        return $query;
    }
}