<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:49
 */

namespace RonasIT\Support\Generators;


use RonasIT\Support\Exceptions\ClassNotExistsException;

class ServiceGenerator extends EntityGenerator
{
    protected $model;

    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function generate() {
        if ($this->classExists('repositories', "{$this->model}Repository")) {
            $serviceContent = $this->getStub('service', [
                'EntityRepository' => "{$this->model}Repository",
                'NewService' => "{$this->model}Service",
            ]);

            $this->saveClass('services', "{$this->model}Service", $serviceContent);

            return;
        }

        if (!$this->classExists('models', $this->model)) {
            throw new ClassNotExistsException("Model {$this->model} not exists");
        }

        $serviceContent = $this->getStub('service_with_trait', [
            'Model' => $this->model,
            'NewService' => "{$this->model}Service",
        ]);

        $this->saveClass('services', "{$this->model}Service", $serviceContent);
    }
}