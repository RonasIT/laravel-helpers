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

class ControllerGenerator extends EntityGenerator
{
    protected $model;

    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function generate() {
        if ($this->classExists('controllers', "{$this->model}Controller")) {
            throw new ClassAlreadyExistsException("Controller {$this->model}Controller already exists");
        }

        if (!$this->classExists('services', "{$this->model}Service")) {
            throw new ClassNotExistsException("Service {$this->model}Service not exists");
        }

        $controllerContent = $this->getControllerContent($this->model);

        $this->saveClass('controllers', "{$this->model}Controller", $controllerContent);

        $this->createRoutes();

        echo "Created a new Controller: {$this->model}Controller \n";
    }

    protected function getControllerContent($model) {
        return $this->getStub('controller', [
            'Entity' => $model
        ]);
    }

    protected function createRoutes() {
        $routesPath = base_path($this->paths['routes']);

        if (!file_exists($routesPath)) {
            throw new FileNotFoundException();
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
                echo "Created a new Route: $route\n";
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