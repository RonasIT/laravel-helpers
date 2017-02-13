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
use RonasIT\Support\Exceptions\CircularRelationsFoundedException;
use RonasIT\Support\Exceptions\ModelFactoryNotFound;
use RonasIT\Support\Exceptions\ClassNotExistsException;
use RonasIT\Support\Exceptions\ModelFactoryNotFoundedException;
use RonasIT\Support\Events\SuccessCreateMessage;

class TestsGenerator extends EntityGenerator
{
    protected $model;
    protected $fields;
    protected $relations;
    protected $fieldsValues;
    protected $annotationReader;
    protected $fakerMethods = [];
    protected $fakerProperties = [];

    protected $getFields = [];
    protected $createFields = [];
    protected $updateFields = [];

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
        if (!$this->checkExistModelFactory()) {
            $content = $this->getStub('tests.factory', [
                'Entity' => $this->model,
                '/*fields*/' => $this->getFactoryFieldsContent()
            ]);
            $createMessage = "Created a new Test factory for {$this->model} model in '{$this->paths['factory']}'";

            file_put_contents($this->paths['factory'], $content, FILE_APPEND);
        } else {
            $createMessage = "Factory for {$this->model} model has already created, so new factory not necessary create.";
        }

        event(new SuccessCreateMessage($createMessage));
    }

    protected function createDump() {
        $this->checkExistRelatedModelsFactories();
        $this->prepareRelatedFactories();
        $content = $this->getStub('tests.dump', [
            '/*truncates*/' => $this->getTruncatesContent(),
            '/*inserts*/' => $this->getInsertsContent(),
            'entities' => $this->getTableName($this->model)
        ]);
        $createMessage = "Created a new Test dump on path: {$this->paths['tests']}/fixtures/{$this->getTestClassName()}/dump.sql";

        mkdir_recursively($this->getFixturesPath());

        file_put_contents($this->getFixturesPath('dump.sql'), $content);

        event(new SuccessCreateMessage($createMessage));
    }

    protected function createTests() {
        $this->generateExistedEntityFixture();
        $this->generateNewEntityFixture();
        $this->generateTest();
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
        $models = $this->getAllModels([$this->model]);

        return array_concat($models, function ($model) {
            return "truncate {$this->getTableName($model)} cascade;\n";
        });
    }

    protected function getInsertsContent() {
        $models = $this->getAllModels([$this->model]);

        return array_concat($models, function ($model) {
            return $this->getStub('tests.insert', [
                'entities' => $this->getTableName($model),
                '/*fields*/' => $this->getFieldsListContent($model),
                '/*values*/' => $this->getValuesListContent($model)
            ]);
        });
    }

    protected function getFieldsListContent($model) {
        $fields = $this->getModelFields($model);

        return implode(', ', $fields);
    }

    protected function getValuesListContent($model) {
        $this->fieldsValues = $this->getValues($model);

        foreach ($this->fieldsValues as $key => $value) {
            if (in_array($key, $this->fields['timestamp']) || in_array($key, $this->fields['timestamp-required'])) {
                $this->getFields[$key] = "'" . $value->format('Y-m-d h:i:s') . "'";
            } else {
                $this->getFields[$key] = var_export($value, true);
            }
        }

        return implode(', ', $this->getFields);
    }

    protected function getValues($model) {
        $modelFields = $this->getModelFields($model);
        $mockEntity = $this->getMockModel($model);

        $result = [];

        foreach ($modelFields as $field) {
            $value = array_get($mockEntity, $field, 1);

            $result[$field] = $value;
        }

        return $result;
    }

    protected function getModelClass($model) {
        return "App\\Models\\{$model}";
    }

    protected function getModelFields($model) {
        $modelClass = $this->getModelClass($model);

        return $modelClass::getFields();
    }

    protected function getMockModel($model) {
        $modelClass = $this->getModelClass($model);

        return factory($modelClass)
            ->make()
            ->toArray();
    }

    public function getFixturesPath($fileName = null) {
        $path = base_path("{$this->paths['tests']}/fixtures/{$this->getTestClassName()}");

        if (empty($fileName)) {
            return $path;
        }

        return "{$path}/{$fileName}";
    }

    public function getTestClassName() {
        return "{$this->model}Test";
    }

    public function getFieldsContent($fields) {
        $lines = array_map(function ($key, $value) {
            if (in_array($key, $this->fields['timestamp']) || in_array($key, $this->fields['timestamp-required'])) {
                $value = $value->format('\'Y-m-d h:i:s\'');
            } else {
                $value = var_export($value, true);
            }

            return "'{$key}' => {$value}";
        }, array_keys($fields), $fields);

        return implode(",\n            ", $lines);
    }

    protected function generateNewEntityFixture() {
        $this->createFields = $this->getMockModel($this->model);
        $fields = $this->prepareFieldsContent($this->createFields);
        $entity = Str::lower($this->model);

        $this->generateFixture(
            "new_{$entity}.json",
            $fields
        );
    }

    protected function generateExistedEntityFixture() {
        $entity = Str::lower($this->model);
        $fields = $this->prepareFieldsContent($this->fieldsValues);

        $this->generateFixture(
            "{$entity}.json",
            $fields
        );
    }

    protected function generateFixture($fixtureName, $data) {
        $fixturePath = $this->getFixturesPath($fixtureName);
        $content = json_encode($data, JSON_PRETTY_PRINT);
        $fixtureRelativePath = "{$this->paths['tests']}/fixtures/{$this->getTestClassName()}/{$fixtureName}";
        $createMessage = "Created a new Test fixture on path: {$fixtureRelativePath}";

        file_put_contents($fixturePath, $content);

        event(new SuccessCreateMessage($createMessage));
    }

    protected function generateTest() {
        $content = $this->getStub('tests.test', [
            'Entity' => $this->model,
            'entities' => $this->getTableName($this->model),
            'entity' => Str::lower($this->model),
            '/*fields*/' => $this->getFieldsContent($this->createFields)
        ]);
        $testName = $this->getTestClassName();
        $createMessage = "Created a new Test: {$testName}";

        $this->saveClass('tests', $testName, $content);

        event(new SuccessCreateMessage($createMessage));
    }

    protected function prepareRelatedFactories() {
        $relations = array_merge(
            $this->relations['hasOne'],
            $this->relations['hasMany']
        );

        foreach ($relations as $relation) {
            $modelFactoryContent = file_get_contents($this->paths['factory']);

            if (!str_contains($modelFactoryContent, $this->getModelClass($relation))) {
                $this->throwFailureException(
                    ModelFactoryNotFound::class,
                    "Model factory for mode {$relation} not found.",
                    "Please create it and after thar you can run this command with flag '--only-tests'."
                );
            }

            $matches = [];

            preg_match($this->getFactoryPattern($relation), $modelFactoryContent, $matches);

            foreach ($matches as $match) {
                $field = Str::lower($this->model) . '_id';

                $newField = "\n        \"{$field}\" => 1,";

                $modelFactoryContent = str_replace($match, $match . $newField, $modelFactoryContent);
            }

            file_put_contents($this->paths['factory'], $modelFactoryContent);
        }
    }

    protected function getFactoryPattern($model) {
        $modelNamespace = "App\\\\Models\\\\" . $model;
        $return = "return \\[";

        return "/{$modelNamespace}.*{$return}/sU";
    }

    protected function getAllModels($models) {
        foreach ($models as $model) {
            $relations = $this->getRelatedModels($model);

            if (empty($relations)) {
                continue;
            }

            if (in_array($this->model, $relations)) {
                $this->throwFailureException(
                    CircularRelationsFoundedException::class,
                    "Circular relations founded.",
                    "Please resolve you relations in models, factories and database."
                );
            }

            $relatedModels = $this->getAllModels($relations);

            $models = array_merge($relatedModels, $models);
        }

        return array_unique($models);
    }

    protected function getRelatedModels($model) {
        $content = $this->getModelClassContent($model);

        preg_match_all('/(?<=belongsTo\().*(?=::class)/', $content, $matches);

        return head($matches);
    }

    protected function getModelClassContent($model) {
        $path = base_path("{$this->paths['models']}/{$model}.php");

        if (!$this->classExists('models', $model)) {
            $this->throwFailureException(
                ClassNotExistsException::class,
                "Cannot create {$model} Model cause {$model} Model does not exists.",
                "Create a {$model} Model by himself or run command 'php artisan make:entity {$model} --only-model'."
            );
        }

        return file_get_contents($path);
    }

    protected function checkExistRelatedModelsFactories() {
        $modelFactoryContent = file_get_contents($this->paths['factory']);
        $relatedModels = $this->getRelatedModels($this->model);

        foreach ($relatedModels as $relatedModel) {
            $relatedFactoryClass = "App\\Models\\$relatedModel::class";
            $existModelFactory = strpos($modelFactoryContent, $relatedFactoryClass);

            if (!$existModelFactory) {
                $this->throwFailureException(
                    ModelFactoryNotFoundedException::class,
                    "Not found $relatedModel factory for $relatedModel model in '{$this->paths['factory']}",
                    "Please declare a factory for $relatedModel model on '{$this->paths['factory']}' path and run your command with option '--only-tests'."
                );
            }
        }
    }

    protected function checkExistModelFactory() {
        $modelFactoryContent = file_get_contents($this->paths['factory']);
        $factoryClass = "App\\Models\\$this->model::class";

        return strpos($modelFactoryContent, $factoryClass);
    }

    protected function prepareFieldsContent($content) {
        foreach ($content as $key => $value) {
            $type = gettype($value);

            if ($this->checkDatetimeObject($value)) {
                $content[$key] = $value->format('Y-m-d h:i:s');

                continue;
            }

            if (($type != 'bool') && ($type != 'int')) {
                $content[$key] = trim($value, "'");
            }
        }

        return $content;
    }

    protected function checkDatetimeObject($content) {
        if ((gettype($content) == 'object') && (get_class($content) == 'DateTime')) {
            return true;
        }

        return false;
    }
}