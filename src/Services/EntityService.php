<?php

namespace RonasIT\Support\Services;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Repositories\BaseRepository;

/**
 * @template TModel of Model = Model
 *
 * @mixin BaseRepository<TModel>
 *
 * @method $this with(array|string $relations)
 * @method $this withCount(array|string $relations)
 * @method $this force($value = true)
 * @method $this withTrashed($enable = true)
 * @method $this onlyTrashed($enable = true)
 *
 * @property BaseRepository<TModel> $repository
 */
class EntityService
{
    protected $repository;

    public function setRepository($repository): self
    {
        $this->repository = app($repository);

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->repository, $name)) {
            $result = call_user_func_array([$this->repository, $name], $arguments);

            if ($result === $this->repository) {
                return $this;
            }

            return $result;
        }

        $className = get_class($this);

        throw new BadMethodCallException("Method {$name} does not exists in {$className}.");
    }
}
