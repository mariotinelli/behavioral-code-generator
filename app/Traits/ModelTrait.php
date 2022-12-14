<?php

namespace App\Traits;


trait ModelTrait {

    use FunctionsTrait;

    protected function makeModels() : void
    {
        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {

            $fillable = '';
            $properties = $schema['properties'];
            foreach ($properties as $key => $property) {
                $fillable = $fillable == '' ? "'$key'" : "$fillable,  '$key'";
            }

            $tableName = $this->camelCaseToSnakeCase($className);

            $fileDirTemplate = __DIR__ . '\..\Templates\Models\TemplateModel.text';
            $fileDirWrite = __DIR__ . "\..\Models\\$className.php";
            $modelStrings = ['//fillable' => $fillable, '//ClassName' => $className, '//table' => $tableName];
            $this->replaceWriteTemplates($fileDirTemplate, $fileDirWrite, $modelStrings);
        }
    }

}
