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
            throw new ClassAlreadyExistsException("Model {$this->name} already exists");
        }

        $this->prepareRelatedModels();
        $modelContent = $this->getNewModelContent();
        $this->saveClass('models', $this->name, $modelContent);

        echo "Created a new Model: {$this->name}\n";
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
                throw new ClassNotExistsException("Model {$relation} does not exists");
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