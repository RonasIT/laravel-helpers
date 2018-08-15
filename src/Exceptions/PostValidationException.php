<?php

namespace RonasIT\Support\Exceptions;

use RonasIT\Support\Exceptions\EntityCreateException;

class PostValidationException extends EntityCreateException
{
    protected $data = [];

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }
}