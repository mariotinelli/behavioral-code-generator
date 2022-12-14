<?php

namespace App\Traits;


trait FunctionsTrait {

    protected function transformToSigular(String $attribute): string
    {
        return (preg_match('~s$~i', $attribute) > 0) ? rtrim($attribute, 's') : $attribute;
    }

    protected function prepareClassName(String $namePath): string
    {

        $explodedRoutes = explode('/', $namePath);
        $originalClassName = $this->transformToSigular($explodedRoutes[0] == '' ? $explodedRoutes[1] : $$explodedRoutes[0]);

        $camelClassName = str_replace('_', ' ', $originalClassName);
        $camelClassName = str_replace('-', ' ', $camelClassName);

        $camelClassName = $this->transformToSigular(ucwords($camelClassName));

        return str_replace(' ', '', $camelClassName);
    }

    function camelCaseToSnakeCase(String $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    function snakeCaseToCamelCase(String $string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }

    protected function getComponentAttributes(String $modelName): string
    {

        $attributes = '';
        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {
            if($className == $modelName) {

                $properties = $schema['properties'];
                foreach ($properties as $attribute => $property) {
                    $request = '$request->' . $attribute;
                    $attributes = $attributes == '' ? "'$attribute' => $request" : "$attributes, \n            '$attribute' => $request";
                }
                return $attributes;
            }
        }

        return $attributes;
    }

    protected function getRequestAttributes(array $requestBody): string
    {

        $attributes = '';
        $properties = $requestBody['properties'];

        foreach ($properties as $attribute => $property) {
            $request = '$request->' . $attribute;
            $attributes = $attributes == '' ? "'$attribute' => $request" : "$attributes, \n            '$attribute' => $request";
        }

        return $attributes . "\n       ";

    }

    protected function getComponentValidation(String $httpMethod, String $modelName): array
    {

        $schema = $this->data['components']['schemas'][$modelName];

        $properties = $schema['properties'];
        $required = $schema['required'];

        $validations = [];
        foreach ($properties as $attribute => $property) {
            $requiredSometimes = $httpMethod == 'post' ? 'required' : 'sometimes';
            $validation =  in_array($attribute, $required) ? [$attribute => $requiredSometimes] : [$attribute => 'nullable'];
            $validations = array_merge($validations, $validation);
        }

        return $validations;
    }

    protected function getRequestValidation(String $httpMethod, array $requestBody): array
    {

        $properties = $requestBody['properties'];
        $required = $requestBody['required'];

        $validations = [];
        foreach ($properties as $attribute => $property) {
            $validation = in_array($attribute, $required) ? [$attribute => 'required'] : [$attribute => 'nullable'];
            $validations = array_merge($validations, $validation);
        }

        return $validations;
    }

    protected function replaceWriteTemplates(String $fileDirTemplate, String $fileDirWrite, array $modelStrings): void
    {

        $fileContentTemplate = file_get_contents($fileDirTemplate);

        /*
            + templateString => é o index do array, que contém a string que serve como template para o replace
            + modelString => é o valor do array, que contém a string que será inserida no lugar do templateString
        */
        foreach ($modelStrings as $templateString => $modelString) {
            $fileContentTemplate = str_replace($templateString, $modelString, $fileContentTemplate);
        }

        $file = fopen($fileDirWrite, 'w');
        fwrite($file, $fileContentTemplate);
        fclose($file);

    }

    protected function replaceTemplates(String $fileDirTemplate, array $modelStrings): string
    {

        $fileContentTemplate = file_get_contents($fileDirTemplate);

        /*
            + templateString => é o index do array, que contém a string que serve como template para o replace
            + modelString => é o valor do array, que contém a string que será inserida no lugar do templateString
        */
        foreach ($modelStrings as $templateString => $modelString) {
            $fileContentTemplate = str_replace($templateString, $modelString, $fileContentTemplate);
        }

        return $fileContentTemplate;
    }

    protected function replaceStringTemplates(String $fileContentTemplate, array $modelStrings): string
    {

        foreach ($modelStrings as $templateString => $modelString) {
            $fileContentTemplate = str_replace($templateString, $modelString, $fileContentTemplate);
        }

        return $fileContentTemplate;
    }


    private function formatTypeParam($typeParam) {

        switch ($typeParam) {
            case 'integer':
                return 'int';
            case 'string':
                return 'string';
            case 'number':
                return 'float';
            default:
                return $typeParam;
        }
    }



    private function makeValidation($schema, $httpMethod, $modelName): string
    {
        $arrValidations = isset($schema['$ref']) ? $this->getComponentValidation($httpMethod, $modelName) : $this->getRequestValidation($httpMethod, $schema);

        $validations = '';
        foreach ($arrValidations as $attribute => $validation) {
            $validations = $validations == '' ? "'$attribute' => '$validation'," : $validations . "\n" . "            '$attribute' => '$validation',";
        }

        $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplateValidate.text';
        $modelStrings = ['//validations' => $validations];
        $validations = $this->replaceTemplates($fileDirTemplate, $modelStrings);

        return $validations;
    }

}
