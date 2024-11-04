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
        $uri = $this->generateNovaUri($resourceClass);

        return $this->json('post', $uri, $data);
    }

    protected function novaUpdateResourceAPICall(string $resourceClass, int $resourceId, ?array $data = []): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass);

        return $this->json('put', "{$uri}/{$resourceId}", $data);
    }

    protected function novaGetResourceAPICall(string $resourceClass, int $resourceId, ?array $data = []): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass);

        return $this->json('get', "{$uri}/{$resourceId}", $data);
    }

    protected function novaSearchResourceAPICall(string $resourceClass, ?array $request = []): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass);

        return $this->json('get', $uri, $request);
    }

    protected function novaGetCreationFieldsAPICall(string $resourceClass): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass, '/creation-fields');

        return $this->json('get', $uri);
    }

    protected function novaRunActionAPICall(string $resourceClass, string $actionClass, ?array $request = []): TestResponse
    {
        $actionUri = app($actionClass)->uriKey();

        $uri = $this->generateNovaUri($resourceClass, "/action?action={$actionUri}");

        return $this->json('post', $uri, $request);
    }

    protected function novaGetActionsAPICall(string $resourceClass, array $resourceIds): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass, '/actions');

        $request = [
            'resources' => implode(',', $resourceIds),
        ];

        return $this->json('get', $uri, $request);
    }

    protected function novaDeleteResourceAPICall(string $resourceClass, array $resourceIds): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass);

        $request = [
            'resources' => $resourceIds,
        ];

        return $this->json('delete', $uri, $request);
    }

    protected function novaGetUpdatableFieldsAPICall(string $resourceClass, int $resourceId): TestResponse
    {
        $uri = $this->generateNovaUri($resourceClass, "/{$resourceId}/update-fields");

        return $this->json('get', $uri);
    }

    protected function novaActingAs(?Authenticatable $user = null): TestCase|self
    {
        return (empty($user))
            ? $this
            : $this->actingAs($user, 'web');
    }

    protected function generateNovaUri(string $modelClass, string $path = ''): string
    {
        $modelName = Str::afterLast($modelClass, '\\');

        $modelName = Str::kebab($modelName);

        return "/nova-api/{$modelName}-resources{$path}";
    }
}
