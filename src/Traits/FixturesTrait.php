<?php

namespace RonasIT\Support\Traits;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Facades\DB;

trait FixturesTrait
{
    protected static $tables;

    protected function loadTestDump($truncateExcept = ['migrations', 'password_resets'],
                                    $prepareSequencesExcept = ['migrations', 'password_resets', 'settings'])
    {
        $dump = $this->getFixture('dump.sql', false);

        if (empty($dump)) {
            return;
        }

        $databaseTables = $this->getTables();
        $scheme = config('database.default');

        $this->clearDatabase($scheme, $databaseTables, $truncateExcept);

        DB::unprepared($dump);

        if ($scheme === 'pgsql') {
            $this->prepareSequences($databaseTables, $prepareSequencesExcept);
        }
    }

    public function getFixturePath($fn)
    {
        $class = get_class($this);
        $explodedClass = explode('\\', $class);
        $className = array_last($explodedClass);

        return base_path("tests/fixtures/{$className}/{$fn}");
    }

    public function getFixture($fn, $failIfNotExists = true)
    {
        $path = $this->getFixturePath($fn);

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        if ($failIfNotExists) {
            $this->fail($path . ' fixture does not exist');
        }

        return '';
    }

    public function getJsonFixture($fn, $assoc = true)
    {
        return json_decode($this->getFixture($fn), $assoc);
    }

    public function getJsonResponse()
    {
        $response = $this->response->getContent();

        return json_decode($response, true);
    }

    public function assertEqualsFixture($fixture, $data)
    {
        $this->assertEquals($this->getJsonFixture($fixture), $data);
    }

    public function exportJsonResponse($fixture)
    {
        $response = $this->getJsonResponse();
        $content = json_encode($response, JSON_PRETTY_PRINT);

        return file_put_contents($this->getFixturePath($fixture), $content);
    }

    public function callRawRequest($method, $uri, $content, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call($method, $uri, [], [], [], $server, $content);

        return $this;
    }

    public function exportJson($fixture, $data)
    {
        if ($data instanceof TestResponse) {
            $data = $data->json();
        }

        file_put_contents(
            $this->getFixturePath($fixture),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function clearDatabase($scheme, $tables, $except)
    {
        if ($scheme === 'pgsql') {
            $query = $this->getClearPsqlDatabaseQuery($tables, $except);
        } elseif ($scheme === 'mysql') {
            $query = $this->getClearMySQLDatabaseQuery($tables, $except);
        }

        if (!empty($query)) {
            app('db.connection')->unprepared($query);
        }
    }

    public function getClearPsqlDatabaseQuery($tables, $except = ['migrations'])
    {
        return array_concat($tables, function ($table) use ($except) {
            if (in_array($table, $except)) {
                return '';
            } else {
                return "TRUNCATE {$table} RESTART IDENTITY CASCADE; \n";
            }
        });
    }

    public function getClearMySQLDatabaseQuery($tables, $except = ['migrations'])
    {
        $query = "SET FOREIGN_KEY_CHECKS = 0;\n";

        $query .= array_concat($tables, function ($table) use ($except) {
            if (in_array($table, $except)) {
                return '';
            } else {
                return "TRUNCATE TABLE {$table}; \n";
            }
        });

        return  "{$query} SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    public function prepareSequences($tables, $except)
    {
        $query = array_concat($tables, function ($table) use ($except) {
            if (in_array($table, $except)) {
                return '';
            } else {
                return "SELECT setval('{$table}_id_seq', (select max(id) from {$table}));\n";
            }
        });

        app('db.connection')->unprepared($query);
    }

    public function exportFile($response, $fixture)
    {
        $this->exportContent(
            file_get_contents($response->getFile()->getPathName()),
            $fixture
        );
    }

    protected function getTables()
    {
        if (empty(self::$tables)) {
            self::$tables = app('db.connection')
                ->getDoctrineSchemaManager()
                ->listTableNames();
        }

        return self::$tables;
    }

    protected function exportContent($content, $fixture)
    {
        file_put_contents(
            $this->getFixturePath($fixture),
            $content
        );
    }
}