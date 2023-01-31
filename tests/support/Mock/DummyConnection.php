<?php

namespace RonasIT\Support\Tests\Support\Mock;

use Illuminate\Database\MySqlConnection;
use Closure;
use PDOException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;

class DummyConnection extends MySqlConnection
{
    const DRIVER = 'mysql';

    protected static $queryCallback;

    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        return self::$queryCallback;
    }

    protected function getDefaultPostProcessor(): Processor
    {
        return new class extends Processor
        {
            public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
            {
                try {
                    return parent::processInsertGetId($query, $sql, $values, $sequence);
                } catch (PDOException $exception) {
                    return 1;
                }
            }
        };
    }

    protected function createTransaction()
    {
    }

    public function commit()
    {
    }

    public static function mock($queryCallback = [])
    {
        self::$queryCallback = $queryCallback;

        static::resolverFor(static::DRIVER, fn (
            $pdo,
            string $database,
            string $prefix,
            array $config
        ) => app(static::class, compact('pdo', 'database', 'prefix', 'config')));

        app('db')->purge();
    }
}