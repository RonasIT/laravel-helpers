<?php

namespace RonasIT\Support\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait NovaTestTrait
{
    protected function novaSearchParams(array $filters, string $search = '', int $perPage = 25): array
    {
        return [
            'search' => $search,
            'filters' => base64_encode(json_encode($filters)),
            'perPage' => $perPage,
        ];
    }

    protected function novaCreateResource(string $resourceClass, ?array $data = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->postJson($resourceUri, $data);
    }

    protected function novaUpdateResource(string $resourceClass, int $resourceId, ?array $data = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->putJson("{$resourceUri}/{$resourceId}", $data);
    }

    protected function novaGetResource(string $resourceClass, int $resourceId, ?array $data = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->getJson("{$resourceUri}/{$resourceId}", $data);
    }

    protected function novaSearchResource(string $resourceClass, ?array $request = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->json('get', $resourceUri, $request);
    }

    protected function novaGetCreationFields(string $resourceClass): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->getJson("{$resourceUri}/creation-fields");
    }

    protected function novaRunAction(string $resourceClass, string $action, ?array $request = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        $actionUri = app($action)->uriKey();

        return $this->json('POST', "{$resourceUri}/action?action={$actionUri}", $request);
    }

    protected function novaGetActions(string $resourceClass, array $resourceIds): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        $request = [
            'resources' => implode(',', $resourceIds),
        ];

        return $this->json('get', "{$resourceUri}/actions", $request);
    }

    protected function novaDeleteResource(string $resourceClass, array $resourceIds): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        $request = [
            'resources' => $resourceIds,
        ];

        return $this->deleteJson($resourceUri, $request);
    }

    protected function novaGetUpdatableFields(string $resourceClass, int $resourceId): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->getJson("{$resourceUri}/{$resourceId}/update-fields");
    }

    protected function novaActingAs(?Authenticatable $user = null): TestCase|self
    {
        return (empty($user))
            ? $this
            : $this->actingAs($user, 'web');
    }

    protected function getNovaResourceUri(string $modelClass): string
    {
        $modelName = Str::afterLast($modelClass, '\\');

        $modelName = Str::kebab($modelName);

        return "/nova-api/{$modelName}-resources";
    }
}
