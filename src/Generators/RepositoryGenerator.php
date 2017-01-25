<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:33
 */

namespace RonasIT\Support\Generators;


use RonasIT\Support\Exceptions\ClassNotExistsException;
use RonasIT\Support\Events\SuccessCreateMessage;

class RepositoryGenerator extends EntityGenerator
{
    protected $model;

    /** @return $this */
    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function generate()
    {
        if (!$this->classExists('models', $this->model)) {
            throw new ClassNotExistsException("Model {$this->model} not exists");
        }

        $repositoryContent = $this->getRepositoryContent();
        $repositoryName = "{$this->model}Repository";
        $createMessage = "Created a new Repository: {$repositoryName}";

        $this->saveClass('repositories', $repositoryName, $repositoryContent);

        event(new SuccessCreateMessage($createMessage));
    }

    protected function getRepositoryContent() {
        return $this->getStub('repository', [
            'EntityRepository' => "{$this->model}Repository",
            'EntityModel' => $this->model
        ]);
    }
}