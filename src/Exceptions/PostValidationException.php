<?php

namespace RonasIT\Support\Exceptions;

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