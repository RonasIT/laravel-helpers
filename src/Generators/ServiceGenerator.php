<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:49
 */

namespace RonasIT\Support\Generators;


use RonasIT\Support\Exceptions\ClassNotExistsException;
use RonasIT\Support\Events\SuccessCreateMessage;

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
            $serviceName = "{$this->model}Service";
            $createMessage = "Created a new Service: {$serviceName}";

            $this->saveClass('services', $serviceName, $serviceContent);

            event(new SuccessCreateMessage($createMessage));

            return;
        }

        if (!$this->classExists('models', $this->model)) {
            $this->throwFailureException(
                ClassNotExistsException::class,
                "Cannot create {$this->model} Model cause {$this->model} Model does not exists.",
                "Create a {$this->model} Model by himself or run command 'php artisan make:entity {$this->model} --only-model'."
            );
        }

        $serviceContent = $this->getStub('service_with_trait', [
            'Model' => $this->model,
            'NewService' => "{$this->model}Service",
        ]);

        $this->saveClass('services', "{$this->model}Service", $serviceContent);
    }
}