<?php

namespace RonasIT\Support\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RonasIT\Support\Exceptions\ForbiddenExportModeException;

trait FixturesTrait
{
    const string JSON_EXTENSION = '.json';

    protected static $tables;
    protected static $sequences;
    protected $postgisTables = [
        'tiger.addrfeat',
        'tiger.edges',
        'tiger.faces',
        'topology.topology',
        'tiger.place_lookup',
        'topology.layer',
        'tiger.geocode_settings',
        'tiger.geocode_settings_default',
        'tiger.direction_lookup',
        'tiger.secondary_unit_lookup',
        'tiger.state_lookup',
        'tiger.street_type_lookup',
        'tiger.county_lookup',
        'tiger.countysub_lookup',
        'tiger.zip_lookup_all',
        'tiger.zip_lookup_base',
        'tiger.zip_lookup',
        'tiger.county',
        'tiger.state',
        'tiger.place',
        'tiger.zip_state',
        'tiger.zip_state_loc',
        'tiger.cousub',
        'tiger.featnames',
        'tiger.addr',
        'tiger.zcta5',
        'tiger.loader_platform',
        'tiger.loader_variables',
        'tiger.loader_lookuptables',
        'tiger.tract',
        'tiger.tabblock',
        'tiger.bg',
        'tiger.pagc_gaz',
        'tiger.pagc_lex',
        'tiger.pagc_rules',
    ];

    protected array $truncateExceptTables = ['migrations', 'password_resets'];
    protected array $prepareSequencesExceptTables = ['migrations', 'password_resets'];

    protected string $dumpFileName = 'dump.sql';

    protected function loadTestDump(): void
    {
        $dump = $this->getFixture($this->dumpFileName, false);

        if (empty($dump)) {
            return;
        }

        $databaseTables = $this->getTables();

        $this->clearDatabase($databaseTables, array_merge($this->postgisTables, $this->truncateExceptTables));

        Schema::getConnection()->unprepared($dump);
    }

    public function getFixturePath(string $fixtureName): string
    {
        $class = get_class($this);
        $explodedClass = explode('\\', $class);
        $className = Arr::last($explodedClass);

        return base_path("tests/fixtures/{$className}/{$fixtureName}");
    }

    public function getFixture(string $fixtureName, $failIfNotExists = true): string
    {
        $path = $this->getFixturePath($fixtureName);

        if (file_exists($path)) {
            return file_get_contents($path);
        }

        if ($failIfNotExists) {
            $this->fail($path . ' fixture does not exist');
        }

        return '';
    }

    public function getJsonFixture(string $fixtureName, $assoc = true)
    {
        $fixtureName = $this->checkFixtureExtension($fixtureName);

        return json_decode($this->getFixture($fixtureName), $assoc);
    }

    public function assertEqualsFixture(string $fixture, $data, bool $exportMode = false): void
    {
        $globalExportMode = $this->globalExportMode ?? false;

        if ($globalExportMode || $exportMode) {
            $this->exportJson($fixture, $data);
        }

        $this->assertEquals($this->getJsonFixture($fixture), $data);
    }

    public function exportJson($fixture, $data): void
    {
        if ($data instanceof TestResponse) {
            $data = $data->json();
        }

        $fixture = $this->checkFixtureExtension($fixture);

        $this->exportContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $fixture);
    }

    public function clearDatabase(array $tables, array $except): void
    {
        $scheme = config('database.default');

        if ($scheme === 'pgsql') {
            $query = $this->getClearPsqlDatabaseQuery($tables, $except);
        } elseif ($scheme === 'mysql') {
            $query = $this->getClearMySQLDatabaseQuery($tables, $except);
        }

        if (!empty($query)) {
            app('db.connection')->unprepared($query);
        }
    }

    public function getClearPsqlDatabaseQuery(array $tables, array $except = ['migrations']): string
    {
        return array_concat($tables, function ($table) use ($except) {
            if (in_array($table['name'], $except)) {
                return '';
            } else {
                return "TRUNCATE \"{$table['name']}\" RESTART IDENTITY CASCADE;\n";
            }
        });
    }

    public function getClearMySQLDatabaseQuery(array $tables, array $except = ['migrations']): string
    {
        $query = "SET FOREIGN_KEY_CHECKS = 0;\n";

        $query .= array_concat($tables, function ($table) use ($except) {
            if (in_array($table['name'], $except)) {
                return '';
            } else {
                return "TRUNCATE TABLE \"{$table['name']}\";\n";
            }
        });

        return  "{$query} SET FOREIGN_KEY_CHECKS = 1;\n";
    }

    public function prepareSequences(array $except = []): void
    {
        $except = array_merge($this->postgisTables, $this->prepareSequencesExceptTables, $except);

        $query = array_concat($this->getSequences(), function ($item) use ($except) {
            if (
                in_array($item->table_name, $except)
                || in_array("{$item->table_schema}.{$item->table_name}", $except)
            ) {
                return '';
            } else {
                $sequenceName = str_replace(["nextval('", "'::regclass)"], '', $item->column_default);
                $tableName = "{$item->table_schema}.{$item->table_name}";

                return "SELECT setval('{$sequenceName}', (select coalesce(max({$item->column_name}), 1) from " .
                    "{$tableName}), (case when (select max({$item->column_name}) from {$tableName}) " .
                    "is NULL then false else true end));\n";
            }
        });

        app('db.connection')->unprepared($query);
    }

    public function exportFile(TestResponse $response, string $fixture): void
    {
        $this->exportContent(
            file_get_contents($response->getFile()->getPathName()),
            $fixture
        );
    }

    protected function getTables(): array
    {
        if (empty(self::$tables)) {
            self::$tables = Schema::getTables();
        }

        return self::$tables;
    }

    protected function getSequences()
    {
        if (empty(self::$sequences)) {
            self::$sequences = app('db.connection')
                ->table('information_schema.columns')
                ->select('table_name', 'table_schema', 'column_name', 'column_default')
                ->where('column_default', 'LIKE', 'nextval%')
                ->get()
                ->toArray();
        }

        return self::$sequences;
    }

    protected function exportContent(string $content, string $fixture): void
    {
        if (env('FAIL_EXPORT_JSON', true)) {
            throw new ForbiddenExportModeException();
        }

        $path = $this->getFixturePath($fixture);

        $this->makeFixtureDir($path);

        file_put_contents($path, $content);
    }

    protected function makeFixtureDir(string $path): void
    {
        $dir = Str::beforeLast($path, '/');

        if (!is_dir($dir)) {
            mkdir(
                directory: $dir,
                recursive: true,
            );
        }
    }

    protected function checkFixtureExtension(string $fixture): string
    {
        return str_contains($fixture, '.')
            ? $fixture
            : $fixture . self::JSON_EXTENSION;
    }
}
