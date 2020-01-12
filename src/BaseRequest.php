<?php

namespace RonasIT\Support;

use Illuminate\Foundation\Http\FormRequest;
use RonasIT\Support\AutoDoc\Traits\AutoDocRequestTrait;

class BaseRequest extends FormRequest
{
    use AutoDocRequestTrait;
    
    public function authorize()
    {
        return true;
    }
    
    public function onlyValidated($keys = null, $default = null)
    {
        $rules = array_keys($this->rules());

        $validatedFields = $this->filterOnlyValidated(parent::all(), array_undot(array_flip($rules)));

        if (!empty($keys)) {
            return is_array($keys) ? array_only($validatedFields, $keys) : array_get($validatedFields, $keys, $default);
        }

        return $validatedFields;
    }

    protected function filterOnlyValidated($fields, $validation)
    {
        $result = [];

        foreach ($validation as $fieldName => $validatedKeys) {
            if (array_has($fields, $fieldName)) {
                $validatedItem = array_get($fields, $fieldName);

                if ($this->isNotNestedRule($validatedKeys)) {
                    $result[$fieldName] = $validatedItem;
                } elseif (array_has($validatedKeys, '*')) {
                    $result[$fieldName] = $this->processNestedRule($validatedKeys['*'], $validatedItem);
                } else {
                    $result[$fieldName] = $this->filterOnlyValidated($validatedItem, $validatedKeys);
                }
            }
        }

        return $result;
    }

    protected function processNestedRule($validatedKeys, $validatedItem)
    {
        if ($this->isNotNestedRule($validatedKeys)) {
            return $validatedItem;
        }

        return array_map(function ($item) use ($validatedKeys) {
            return $this->filterOnlyValidated($item, $validatedKeys);
        }, $validatedItem);
    }

    protected function isNotNestedRule($validatedKeys)
    {
        return is_integer($validatedKeys);
    }

}