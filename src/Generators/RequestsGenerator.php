<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:55
 */

namespace RonasIT\Support\Generators;

use Illuminate\Support\Str;
use RonasIT\Support\Events\SuccessCreateMessage;

class RequestsGenerator extends EntityGenerator
{
    protected $model;
    protected $fields;

    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function setFields($fields) {
        $this->fields = $fields;
        return $this;
    }

    public function generate() {
        $this->createRequest('Create', $this->getValidationParametersContent($this->fields, true));
        $this->createRequest('Get');
        $this->createRequest('Update', $this->getValidationParametersContent($this->fields, false));
        $this->createRequest('Delete');
    }

    protected function createRequest($method, $parametersContent = '') {
        $content = $this->getStub('request', [
            'Method' => $method,
            'Entity' => $this->model,
            '/*parameters*/' => $parametersContent
        ]);
        $requestName = "{$method}{$this->model}Request";
        $createMessage = "Created a new Request: {$requestName}";

        $this->saveClass('requests', $requestName, $content);

        event(new SuccessCreateMessage($createMessage));
    }

    public function getValidationParametersContent($parameters, $requiredAvailable) {
        $content = '';

        foreach ($parameters as $type => $parameterNames) {
            $explodedType = explode('-', $type);
            $type = $explodedType[0];
            $isRequired = array_has($explodedType, '1');

            foreach ($parameterNames as $name) {
                if ($type == 'belongsTo') {
                    $content .= $this->getRelationValidationContent($name, $requiredAvailable);
                } else {
                    $required = $isRequired && $requiredAvailable;

                    $content .= $this->getValidationContent($name, $type, $required);
                }
            }
        }

        return $content;
    }

    protected function getRelationValidationContent($name, $required) {
        $validation = "integer|exists:{$this->getTableName($name)},id";

        $name = Str::lower($name).'_id';

        return $this->getValidationContent($name, $validation, $required);
    }

    protected function getValidationContent($name, $validation, $required) {
        $replaces = [
            'timestamp' => 'date',
            'float' => 'numeric',
        ];

        foreach ($replaces as $key => $value)
        {
            if ($key == $validation) {
                $validation = $value;
            }
        }

        if ($required) {
            $validation .= '|required';
        }

        return $this->getStub('validation_parameter', [
            'name' => $name,
            'validation' => $validation
        ]);
    }
}