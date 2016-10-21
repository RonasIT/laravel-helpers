<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 12:28
 */

namespace RonasIT\Support\Generators;


use Illuminate\Support\Str;
use Faker\Generator as Faker;

class TestsGenerator extends EntityGenerator
{
    protected $model;
    protected $fields;
    protected $relations;
    protected $annotationReader;
    protected $fakerMethods = [];
    protected $fakerProperties = [];

    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function setFields($fields) {
        $this->fields = $fields;
        return $this;
    }

    /** @return $this*/
    public function setRelations($relations) {
        $this->relations = $relations;

        foreach ($this->relations['belongsTo'] as $relation) {
            $this->fields['integer-require'][] = Str::lower($relation).'_id';
        }

        return $this;
    }

    public function generate() {
        $this->createFactory();
        $this->createDump();
        $this->createTests();
    }

    protected function createFactory() {
        $content = $this->getStub('tests.factory', [
            'Entity' => $this->model,
            '/*fields*/' => $this->getFactoryFieldsContent()
        ]);

        file_put_contents($this->paths['factory'], $content, FILE_APPEND);
    }

    protected function createDump() {

    }

    protected function createTests() {

    }

    protected function getFactoryFieldsContent() {
        $fields = $this->prepareFactoryFields();
        /** @var Faker $faker*/
        $faker = app(Faker::class);

        $fieldLines = array_map(function ($field, $type) use ($faker) {
            $fakerMethods = [
                'integer' => 'randomNumber()',
                'boolean' => 'boolean',
                'string' => 'word',
                'float' => 'randomFloat()',
                'timestamp' => 'dateTime'
            ];

            if (preg_match('/_id$/', $field) || ($field == 'id')) {
                return "        '{$field}' => 1";
            }

            if (property_exists($faker, $field)) {
                return "        '{$field}' => \$faker->{$field}";
            }

            if (method_exists($faker, $field)) {
                return "        '{$field}'=> \$faker->{$field}()";
            }

            return "        '{$field}' => \$faker->{$fakerMethods[$type]}";
        }, array_keys($fields), $fields);

        return implode(",\n", $fieldLines);
    }

    protected function prepareFactoryFields() {
        $result = [];

        foreach ($this->fields as $type => $fields) {
            foreach ($fields as $field) {
                $explodedType = explode('-', $type);

                $result[$field] = head($explodedType);
            }
        }

        return $result;
    }

    protected function getTruncatesContent() {
        $models = $this->getAllModels();

        return array_concat($models, function ($model) {
            return "truncate {$this->getTableName($model)} cascade;\n";
        });
    }

    protected function getInsertsContent() {
        $models = $this->getAllModels();

        return array_concat($models, function ($model) {
            return $this->getStub('tests.insert', [
                'entities' => $this->getTableName($model),
                '/*fields*/' => $this->getFieldsListContent($model),
                '/*values*/' => $this->getValuesListContent($model)
            ]);
        });
    }

    protected function getAllModels() {
        $models = array_collapse($this->relations);
        $models[] = $this->model;

        return $models;
    }

    protected function getFieldsListContent($model) {
        $fields = $this->getModelFields($model);

        return implode(', ', $fields);
    }

    protected function getValuesListContent($model) {
        $modelFields = $this->getModelFields($model);
        $mockEntity = $this->getMockModel($model);
    }

    protected function getModelClass($model) {
        return "\\App\\Models\\{$model}";
    }

    protected function getModelFields($model) {
        $modelClass = $this->getModelClass($model);

        return $modelClass::getFields();
    }

    protected function getMockModel($model) {
        $modelClass = $this->getModelClass($model);

        return factory($modelClass)
            ->make()->toArray();
    }
}