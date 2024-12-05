<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\DB;

class TableTestState extends BaseTestState
{
    public function __construct(
        string $tableName,
        array $jsonFields = [],
        ?string $connectionName = null,
    ) {
        parent::__construct(
            tableName: $tableName,
            jsonFields: $jsonFields,
            connectionName: $connectionName ?? DB::getDefaultConnection(),
        );
    }
}
