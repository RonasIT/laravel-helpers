<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 19.10.16
 * Time: 8:55
 */

namespace RonasIT\Support\Generators;


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

        $this->saveClass('requests', "{$method}{$this->model}Request", $content);
    }

    public function getValidationParametersContent($parameters, $requiredAvailable) {
        $content = '';

        foreach ($parameters as $type => $parameterNames) {
            $explodedType = explode('-', $type);

            $type = $explodedType[0];
            $isRequired = array_has($explodedType, '1');

            foreach ($parameterNames as $name) {
                $replaces = [
                    'name' => $name,
                    'type' => $type
                ];

                if (!$isRequired || !$requiredAvailable) {
                    $replaces['|required'] = '';
                }

                $content .= $this->getStub('validation_parameter', $replaces);
            }
        }

        return $content;
    }
}