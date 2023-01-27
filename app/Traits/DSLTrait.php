<?php

namespace App\Traits;

use App\Actions\BehavioralGenerator;

trait DSLTrait
{
    use FunctionsTrait;

    protected function checkExists(string $script, string $regex): bool
    {
        return preg_match_all($regex, $script, $matches);
    }

    protected function modelProcess(string $script): array|false
    {
        preg_match_all('/Model\((.*?)\)/', $script, $matches);
        $modelName = $matches[1][0];
        return $this->checkMethod($script, $modelName);
    }

    protected function returnProcess(string $script): string|false
    {
        preg_match_all('/Return\((.*?)\)/', $script, $matches);
        $dataReturn = $matches[1][0];
        return $this->makeReturn($dataReturn);
    }

    protected function varProcess(string $script, string $regex): string
    {
        preg_match_all($regex, $script, $matches);
        $stringVar = $matches[0][0];
        $stringVar = str_replace('$', '', $stringVar);
        $stringVar = str_replace('=', '', $stringVar);
        return trim($stringVar);
    }

    private function checkMethod(string $script, string $modelName)
    {
        $hasPost = preg_match_all('/post\((.*?)\)/', $script, $matches);
        if ($hasPost) {
            return $this->makePost($modelName);
        }

        $hasGet = preg_match_all('/get\((.*?)\)/', $script, $matches);
        if ($hasGet) {
            return $this->makeGet($script, $modelName);
        }

        $hasUpdate = preg_match_all('/update\((.*?)\)/', $script, $matches);
        if ($hasUpdate) {
            return $this->makeUpdate($script, $modelName);
        }

        $hasPatch = preg_match_all('/patch\((.*?)\)/', $script, $matches);
        if ($hasPatch) {
            return $this->makePatch($script, $modelName);
        }

        $hasDelete = preg_match_all('/delete\((.*?)\)/', $script, $matches);
        if ($hasDelete) {
            return $this->makeDelete($script, $modelName);
        }

        return false;
    }

    private function makeReturn(string $return): string
    {
        $returnParts = explode(',', $return);
        $data = $returnParts[0];
        $httpCode = $returnParts[1];

        $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Return\TemplateReturn.text';
        $modelStrings = ['//data' => $data, '//httpCode' => $httpCode];
        return $this->replaceTemplates($fileDirTemplate, $modelStrings);
    }

    private function makePost(string $modelName): array
    {
        $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplateCreate.text';
        $modelStrings = ['//model' => $modelName];
        $behavior = $this->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$behavior, $modelName];
    }

    private function makeGet(string $script, string $modelName): array|false
    {
        $hasAll = preg_match_all('/all\(( )*\)/', $script, $matches);
        if ($hasAll) {
            $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplateGetAll.text';
            $modelStrings = ['//model' => $modelName];
            $fileContent = $this->replaceTemplates($fileDirTemplate, $modelStrings);
            return [$fileContent, $modelName];
        }

        $hasFirst = preg_match_all('/first\(( )*\)/', $script, $matches);
        if ($hasFirst) {
            $getParam = $this->getParam('get', $script);

            $modelStrings = [
                '//model' => $modelName,
                '//sqlParam' => $getParam,
                '//requestParam' => $getParam
            ];
            $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplateGetFirst.text';
            $fileContent = $this->replaceTemplates($fileDirTemplate, $modelStrings);

            return [$fileContent, $modelName];
        }

        return false;
    }

    private function makeUpdate($script, $modelName)
    {
        $updateParam = $this->getParam('update', $script);
        $modelStrings = [
            '//model' => $modelName,
            '//sqlParam' => $updateParam,
            '//requestParam' => $updateParam
        ];
        $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplateUpdate.text';
        $fileContent = $this->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

    private function makePatch($script, $modelName)
    {
        $patchParam = $this->getParam('patch', $script);

        $modelStrings = [
            '//model' => $modelName,
            '//sqlParam' => $patchParam,
            '//requestParam' => $patchParam
        ];
        $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplatePatch.text';
        $fileContent = $this->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

    private function makeDelete($script, $modelName)
    {
        $deleteParam = $this->getParam('delete', $script);

        $modelStrings = [
            '//model' => $modelName,
            '//sqlParam' => $deleteParam,
            '//requestParam' => $deleteParam
        ];
        $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\Crud\TemplateDelete.text';
        $fileContent = $this->replaceTemplates($fileDirTemplate, $modelStrings);

        return [$fileContent, $modelName];
    }

    private function getParam($method, $script)
    {
        preg_match_all("/$method\((.*?)\)/", $script, $matches);
        $param = $matches[1][0];

        if (strpos($param, '@') !== false) {
            $param = 'request->' . str_replace('@', '', $param);
        } elseif (strpos($param, '&') !== false) {
            $param = str_replace('&', '', $param);
        } elseif (strpos($param, '$') !== false) {
            $param = str_replace('$', '', $param);
        }

        return $param;
    }
}
