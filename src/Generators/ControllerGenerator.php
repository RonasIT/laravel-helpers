<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:53
 */

namespace RonasIT\Support\Generators;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use RonasIT\Support\Exceptions\ClassAlreadyExistsException;
use RonasIT\Support\Exceptions\ClassNotExistsException;
use RonasIT\Support\Events\SuccessCreateMessage;

class ControllerGenerator extends EntityGenerator
{
    protected $model;

    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function generate() {
        if ($this->classExists('controllers', "{$this->model}Controller")) {
            $this->throwFailureException(
                ClassAlreadyExistsException::class,
                "Cannot create {$this->model}Controller cause {$this->model}Controller already exists.",
                "Remove {$this->model}Controller or run your command with options:'—without-controller'."
            );

        }

        if (!$this->classExists('services', "{$this->model}Service")) {
            $this->throwFailureException(
                ClassNotExistsException::class,
                "Cannot create {$this->model}Service cause {$this->model}Service does not exists.",
                "Create a {$this->model}Service by himself or run your command with options:'--without-controller --without-migrations --without-requests --without-tests'."
            );
        }

        $controllerContent = $this->getControllerContent($this->model);
        $controllerName = "{$this->model}Controller";
        $createMessage = "Created a new Controller: {$controllerName}";

        $this->saveClass('controllers', $controllerName, $controllerContent);
        $this->createRoutes();

        event(new SuccessCreateMessage($createMessage));
    }

    protected function getControllerContent($model) {
        return $this->getStub('controller', [
            'Entity' => $model
        ]);
    }

    protected function createRoutes() {
        $routesPath = base_path($this->paths['routes']);

        if (!file_exists($routesPath)) {
            $this->throwFailureException(
                FileNotFoundException::class,
                "Not found file with routes.",
                "Create a routes file on path: '{$routesPath}'."
            );
        }

        $this->addUseController($routesPath);
        $this->addRoutes($routesPath);
    }

    protected function addRoutes($routesPath) {
        $routesContent = $this->getStub('routes', [
            'Entity' => $this->model,
            'entities' => $this->getTableName($this->model)
        ]);
        $routes = explode("\n", $routesContent);

        foreach ($routes as $route) {
            if (!empty($route)) {
                $createMessage = "Created a new Route: $route";

                event(new SuccessCreateMessage($createMessage));
            }
        }

        return file_put_contents($routesPath, $routesContent, FILE_APPEND);
    }

    protected function addUseController($routesPath) {
        $routesFileContent = file_get_contents($routesPath);

        $stub = $this->getStub('use_routes', [
            'Entity' => $this->model
        ]);

        $routesFileContent = str_replace("<?php\n", $stub, $routesFileContent);

        file_put_contents($routesPath, $routesFileContent);
    }
}