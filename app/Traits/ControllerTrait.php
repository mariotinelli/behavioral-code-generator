<?php

namespace App\Traits;


trait ControllerTrait{

    use MethodTrait, FunctionsTrait;

    protected function makeControllers(): void
    {
        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {
            $controllerName = $className . "Controller";
            $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\TemplateController.text';
            $fileDirWrite = __DIR__ . "\..\Http\Controllers\\$controllerName.php";
            $modelStrings = ['//ClassName' => $controllerName];

            $this->replaceWriteTemplates($fileDirTemplate, $fileDirWrite, $modelStrings);
            $this->makeMethods($className);

        }

    }

}
