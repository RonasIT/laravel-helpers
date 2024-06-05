<?php

namespace RonasIT\Support\Traits;

trait NovaTestTrait
{
    public function novaSearchParams(array $filters, string $search = '', int $perPage = 25): array
    {
        return [
            'search' => $search,
            'filters' => base64_encode(json_encode($filters)),
            'perPage' => $perPage,
        ];
    }
}
