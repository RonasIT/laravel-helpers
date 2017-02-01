<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 10:22
 */

namespace RonasIT\Support\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * @property Filesystem $fs
 */
abstract class EntityGenerator
{
    protected $paths = [];

    public function __construct()
    {
        $this->paths = config('entity-generator.paths');
    }

    abstract public function generate();

    protected function classExists($path, $name) {
        $entitiesPath = $this->paths[$path];

        $classPath = base_path("{$entitiesPath}/{$name}.php");

        return file_exists($classPath);
    }

    protected function saveClass($path, $name, $content) {
        $entitiesPath = $this->paths[$path];

        $classPath = base_path("{$entitiesPath}/{$name}.php");

        if (!file_exists($entitiesPath)) {
            mkdir_recursively(base_path($entitiesPath));
        }

        return file_put_contents($classPath, $content);
    }

    protected function getStub($stub, $replaces = []) {
        $stubPath = config("entity-generator.stubs.$stub");

        $stub = file_get_contents($stubPath);
        foreach ($replaces as $search => $replace) {
            $stub = str_replace($search, $replace, $stub);
        }

        return $stub;
    }

    protected function getTableName($entityName) {
        $entityName = snake_case($entityName);

        return Str::plural($entityName);
    }

    protected function throwFailureException($exceptionClass, $failureMessage, $recommendedMessage) {
        throw new $exceptionClass("{$failureMessage} {$recommendedMessage}");
    }
}