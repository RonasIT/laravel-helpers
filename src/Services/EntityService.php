<?php

namespace RonasIT\Support\Services;

use BadMethodCallException;

/**
 * @property BaseRepository $repository
 */
class EntityService
{
    protected $repository;

    public function setRepository($repository)
    {
        $this->repository = app($repository);

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->repository, $name)) {
            return call_user_func_array([$this->repository, $name], $arguments);
        }

        throw new BadMethodCallException();
    }
}