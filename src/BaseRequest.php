<?php

namespace RonasIT\Support;

use Illuminate\Support\Arr;
use Illuminate\Foundation\Http\FormRequest;
use RonasIT\Support\AutoDoc\Traits\AutoDocRequestTrait;

class BaseRequest extends FormRequest
{
    use AutoDocRequestTrait;

    public function authorize()
    {
        return true;
    }

    /**
     * @param array|string $keys
     * @param mixed $default
     *
     * Sorts and filters request parameters. Returns parameters specified only in the function rules().
     * It needs to avoid troubles in cases where array-parameter declared in rules below its content.
     *
     * @return array;
     */

    public function onlyValidated($keys = null, $default = null)
    {
        $rules = array_keys($this->rules());

        $this->sortByStrlen($rules);

        $validatedFields = $this->filterOnlyValidated(parent::all(), array_undot(array_flip($rules)));

        if (!empty($keys)) {
            return is_array($keys) ? Arr::only($validatedFields, $keys) : Arr::get($validatedFields, $keys, $default);
        }

        return $validatedFields;
    }

    protected function filterOnlyValidated($fields, $validation): array
    {
        $result = [];

        foreach ($validation as $fieldName => $validatedKeys) {
            if (Arr::has($fields, $fieldName) || $fieldName === '*') {
                $validatedItem = Arr::get($fields, $fieldName);

                if ($this->isNotNestedRule($validatedKeys)) {
                    $result[$fieldName] = $validatedItem;
                } elseif (Arr::has($validatedKeys, '*')) {
                    $result[$fieldName] = $this->processNestedRule($validatedKeys['*'], $validatedItem);
                } elseif ($fieldName === '*') {
                    $result = $this->processNestedRule($validatedKeys, $fields);
                } else {
                    $result[$fieldName] = $this->filterOnlyValidated($validatedItem, $validatedKeys);
                }
            }
        }

        return $result;
    }

    protected function processNestedRule($validatedKeys, $validatedItem): array
    {
        if ($this->isNotNestedRule($validatedKeys)) {
            return $validatedItem;
        }

        return array_map(function ($item) use ($validatedKeys) {
            return $this->filterOnlyValidated($item, $validatedKeys);
        }, $validatedItem);
    }

    protected function isNotNestedRule($validatedKeys): bool
    {
        return is_integer($validatedKeys);
    }

    private function sortByStrlen(array &$array)
    {
        $collection = collect($array)->sortBy(function ($string) {
            return strlen($string);
        });

        $array = $collection->toArray();
    }
}
