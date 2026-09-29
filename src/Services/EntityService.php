<?php

namespace RonasIT\Support\Services;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RonasIT\Support\Repositories\BaseRepository;

/**
 * @property BaseRepository $repository
 */
class EntityService
{
    protected $repository;

    public function setRepository($repository): self
    {
        $this->repository = app($repository);

        return $this;
    }

    /**
     * Uses first() and create() of the service, so business logic of overridden methods is applied
     */
    public function firstOrCreate(array $where, array $data = []): Model
    {
        return $this->first($where) ?? $this->createOrFirst($where, $data);
    }

    /**
     * Uses first(), create() and update() of the service, so business logic of overridden methods is applied
     */
    public function updateOrCreate(array $where, array $data): Model
    {
        $entity = $this->first($where) ?? $this->createOrFirst($where, $data);

        return ($entity->wasRecentlyCreated) ? $entity : $this->update($entity->getKey(), $data);
    }

    protected function createOrFirst(array $where, array $data): Model
    {
        $callback = fn () => $this->create(array_merge($data, $where));

        try {
            return (DB::transactionLevel() > 0) ? DB::transaction($callback) : $callback();
        } catch (UniqueConstraintViolationException $exception) {
            return $this->first($where) ?? throw $exception;
        }
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this->repository, $name)) {
            $className = get_class($this);

            throw new BadMethodCallException("Method {$name} does not exists in {$className}.");
        }

        $result = call_user_func_array([$this->repository, $name], $arguments);

        if ($result === $this->repository) {
            return $this;
        }

        // Settable methods of the repository return its copy, so the service is copied as well
        // to keep business logic of the service methods for the configured repository
        if ($result instanceof $this->repository) {
            $service = clone $this;
            $service->repository = $result;

            return $service;
        }

        return $result;
    }
}
