<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:26
 */

namespace RonasIT\Support\Generators;

use Illuminate\Support\Str;
use RonasIT\Support\Exceptions\ClassAlreadyExistsException;
use RonasIT\Support\Exceptions\ClassNotExistsException;
use RonasIT\Support\Events\SuccessCreateMessage;

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

        foreach ($relations['belongsTo'] as $relation) {
            $this->fields[] = Str::lower($relation).'_id';
        }

        return $this;
    }

    public function generate()
    {
        if ($this->classExists('models', $this->name)) {
            $this->throwFailureException(
                ClassAlreadyExistsException::class,
                "Cannot create {$this->name} Model cause {$this->name} Model already exists.",
                "Remove {$this->name} Model or run your command with options:'—without-model'."
            );
        }

        $this->prepareRelatedModels();
        $modelContent = $this->getNewModelContent();
        $modelName = $this->name;
        $createMessage = "Created a new Model: {$modelName}";

        $this->saveClass('models', $modelName, $modelContent);

        event(new SuccessCreateMessage($createMessage));
    }

    protected function getNewModelContent() {
        return $this->getStub('model', [
            'DummyClass' => $this->name,
            '/*fillable*/' => $this->getFillableContent(),
            '/*relations*/' => $this->getRelationsContent()
        ]);
    }

    protected function getFillableContent() {
        $fields = implode("', '", $this->fields);
        
        if (empty($fields)) {
            return false;
        }

        return "'{$fields}'";
    }

    protected function getRelationsContent() {
        $content = '';

        foreach ($this->relations as $type => $entities) {
            foreach ($entities as $entity) {
                $content .= $this->getStub('relation', [
                    'relationName' => strtolower($entity),
                    'relationType' => $type,
                    'EntityClass' => $entity
                ]);
            }
        }

        return trim($content);
    }

    public function prepareRelatedModels() {
        $relations = array_only($this->relations, ['hasOne', 'hasMany']);
        $relations = array_collapse($relations);

        foreach ($relations as $relation) {
            if (!$this->classExists('models', $relation)) {
                $this->throwFailureException(
                    ClassNotExistsException::class,
                    "Cannot create {$relation} Model cause {$relation} Model does not exists.",
                    "Create a {$relation} Model by himself or run command 'php artisan make:entity {$relation} --only-model'."
                );
            }

            $content = $this->getModelContent($relation);

            $newRelation = $this->getStub('relation', [
                'relationName' => Str::lower($this->name),
                'relationType' => 'belongsTo',
                'EntityClass' => $this->name
            ]);

            $fixedContent = preg_replace('/\}$/', "{$newRelation}\n}", $content);

            $this->saveClass('models', $relation, $fixedContent);
        }
    }

    public function getModelContent($model) {
        $modelPath = base_path($this->paths['models']."/{$model}.php");

        return file_get_contents($modelPath);
    }
}