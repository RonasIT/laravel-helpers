<?php

namespace RonasIT\Support\Tests;

use Illuminate\Support\Facades\DB;

class TableTestState extends BaseTestState
{
    public function __construct(string $tableName, array $jsonFields = [])
    {
        parent::__construct(
            tableName: $tableName,
            jsonFields: $jsonFields,
            connectionName: DB::getDefaultConnection(),
        );
    }
}
