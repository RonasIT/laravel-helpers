<?php

namespace RonasIT\Support\Tests\Support\Mock\Services;

use Illuminate\Database\Eloquent\Model;
use RonasIT\Support\Services\EntityService;
use RonasIT\Support\Tests\Support\Mock\Repositories\TestRepository;

class TestService extends EntityService
{
    public function __construct()
    {
        $this->setRepository(TestRepository::class);
    }

    public function first($where = []): ?Model
    {
        return $this->repository->first($where)?->setAttribute('found_by', 'first');
    }

    public function create(array $data): Model
    {
        $data['json_field'] = ['prepared_by' => 'create'];

        return $this->repository->create($data);
    }

    public function update($where, array $data): ?Model
    {
        $data['json_field'] = ['prepared_by' => 'update'];

        return $this->repository->update($where, $data);
    }
}
