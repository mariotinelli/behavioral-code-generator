<?php

namespace App\Http\Traits;

use App\Actions\BehavioralGenerator;

trait DSLTrait {

    protected function checkModelExists($script, $pathContent): array | false
    {
        $result = preg_match_all("/Model\((.*?)\)/", $script, $matches);
        if ($result) {
            $modelName = $matches[1][0];
            return $this->checkMethod($script, $modelName, $pathContent);
        }
        return false;
    }

    protected function checkReturnExists($script): array | false
    {
        $result = preg_match_all("/Return\((.*?)\)/", $script, $matches);
        if ($result) {
            $return = $matches[1][0];
            return $this->makeReturn($return);
        }
        return false;
    }


    private function checkMethod($script, $modelName, $pathContent)
    {

        $hasPost = preg_match_all("/post\((.*?)\)/", $script, $matches);
        if ($hasPost) { return $this->makePost($modelName, $pathContent); }

        // $hasGet = preg_match_all("/get\((.*?)\)/", $script, $matches);
        // if ($hasGet) { return $this->makeGet($modelName, $pathContent); }

        // $hasUpdate = preg_match_all("/update\((.*?)\)/", $script, $matches);
        // if ($hasUpdate) { return $this->makeUpdate($modelName, $pathContent); }

        // $hasPatch = preg_match_all("/patch\((.*?)\)/", $script, $matches);
        // if ($hasPatch) { return $this->makePatch($modelName, $pathContent); }

        // $hasDelete = preg_match_all("/delete\((.*?)\)/", $script, $matches);
        // if ($hasDelete) { return $this->makeDelete($modelName, $pathContent); }

        return false;
    }

    private function makeReturn(String $return): array
    {
        $returnParts = explode(",", $return);
        $data = $returnParts[0];
        $httpCode = $returnParts[1];

        if (gettype($data) == 'string') {
            $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\Return\TemplateReturn.text';
            $modelStrings = ['//data' => $data, '//httpCode' => $httpCode];
            $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

            return [true, $fileContent];
        }



        // $schema = $pathContent['requestBody']['content']['application/json']['schema'];
        // $attributes = isset($schema['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($schema);

        // $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\TemplateCreate.text';
        // $modelStrings = ['//model' => $modelName, '//attributes' => $attributes];
        // $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

        // return [$fileContent, $modelName];
    }

    private function makePost($modelName, $pathContent)
    {
        $schema = $pathContent['requestBody']['content']['application/json']['schema'];
        $attributes = isset($schema['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($schema);

        $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\TemplateCreate.text';
        $modelStrings = ['//model' => $modelName, '//attributes' => $attributes];
        $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

        return [true, $fileContent, $modelName];
    }

    private function makeGet($modelName, $pathContent)
    {
        $schema = $pathContent['requestBody']['content']['application/json']['schema'];
        $attributes = isset($schema['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($schema);

        $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\TemplateCreate.text';
        $modelStrings = ['//model' => $modelName, '//attributes' => $attributes];
        $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

    private function makeUpdate($modelName, $pathContent)
    {
        $schema = $pathContent['requestBody']['content']['application/json']['schema'];
        $attributes = isset($schema['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($schema);

        $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\TemplateCreate.text';
        $modelStrings = ['//model' => $modelName, '//attributes' => $attributes];
        $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

    private function makePatch($modelName, $pathContent)
    {
        $schema = $pathContent['requestBody']['content']['application/json']['schema'];
        $attributes = isset($schema['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($schema);

        $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\TemplateCreate.text';
        $modelStrings = ['//model' => $modelName, '//attributes' => $attributes];
        $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

    private function makeDelete($modelName, $pathContent)
    {
        $schema = $pathContent['requestBody']['content']['application/json']['schema'];
        $attributes = isset($schema['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($schema);

        $fileDirTemplate = __DIR__ . '\..\..\Templates\Controllers\TemplateCreate.text';
        $modelStrings = ['//model' => $modelName, '//attributes' => $attributes];
        $fileContent = app(BehavioralGenerator::class)->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

}
