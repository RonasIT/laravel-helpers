<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:26
 */

namespace RonasIT\Support\Generators;


use RonasIT\Support\Exceptions\ClassAlreadyExistsException;
use RonasIT\Support\Exceptions\ClassNotExistsException;

class ModelGenerator extends EntityGenerator
{
    protected $name;
    protected $fields;
    protected $relations;

    /** @return $this */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /** @return $this */
    public function setFields($fields) {
        $this->fields = $fields;
        return $this;
    }

    /** @return $this */
    public function setRelations($relations) {
        $this->relations = $relations;
        return $this;
    }

    public function generate()
    {
        if ($this->classExists('models', $this->name)) {
            throw new ClassAlreadyExistsException("Model {$this->name} already exists");
        }

        $modelContent = $this->getModelContent();

        $this->saveClass('models', $this->name, $modelContent);
    }

    protected function getModelContent() {
        return $this->getStub('model', [
            'DummyClass' => $this->name,
            '/*fillable*/' => $this->getFillableContent(),
            '/*relations*/' => $this->getRelationsContent()
        ]);
    }

    protected function getFillableContent() {
        $fields = implode("', '", $this->fields);

        return "\n        '{$fields}'\n    ";
    }

    protected function getRelationsContent() {
        $content = '';

        foreach ($this->relations as $type => $entities) {
            foreach ($entities as $entity) {
                if (!$this->classExists('models', $entity)) {
                    throw new ClassNotExistsException("Model {$entity} does not exists");
                }

                $content .= $this->getStub('relation', [
                    'relationName' => strtolower($entity),
                    'relationType' => $type,
                    'EntityClass' => $entity
                ]);
            }
        }

        return trim($content);
    }
}