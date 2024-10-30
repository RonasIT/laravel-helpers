<?php

namespace RonasIT\Support\Traits;

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

    protected function novaCreateResourceAPICall(string $resourceClass, ?array $data = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->postJson($resourceUri, $data);
    }

    protected function novaUpdateResourceAPICall(string $resourceClass, int $resourceId, ?array $data = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->putJson("{$resourceUri}/{$resourceId}", $data);
    }

    protected function novaGetResourceAPICall(string $resourceClass, int $resourceId, ?array $data = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->getJson("{$resourceUri}/{$resourceId}", $data);
    }

    protected function novaSearchResourceAPICall(string $resourceClass, ?array $request = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->json('get', $resourceUri, $request);
    }

    protected function novaGetCreationFieldsAPICall(string $resourceClass): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        return $this->getJson("{$resourceUri}/creation-fields");
    }

    protected function novaRunActionAPICall(string $resourceClass, string $actionClass, ?array $request = []): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        $actionUri = app($actionClass)->uriKey();

        return $this->json('POST', "{$resourceUri}/action?action={$actionUri}", $request);
    }

    protected function novaGetActionsAPICall(string $resourceClass, array $resourceIds): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        $request = [
            'resources' => implode(',', $resourceIds),
        ];

        return $this->json('get', "{$resourceUri}/actions", $request);
    }

    protected function novaDeleteResourceAPICall(string $resourceClass, array $resourceIds): TestResponse
    {
        $resourceUri = $this->getNovaResourceUri($resourceClass);

        $request = [
            'resources' => $resourceIds,
        ];

        return $this->deleteJson($resourceUri, $request);
    }

    protected function novaGetUpdatableFieldsAPICall(string $resourceClass, int $resourceId): TestResponse
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
