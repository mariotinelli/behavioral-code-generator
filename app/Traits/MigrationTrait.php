<?php

namespace App\Traits;


trait MigrationTrait {

    use FunctionsTrait;

    protected function makeMigrations(): void
    {

        $schemas = $this->data['components']['schemas'];
        foreach ($schemas as $className => $schema) {

            $tableName = $this->camelCaseToSnakeCase($className);

            $fileDirTemplate = __DIR__ . '\..\Templates\Migrations\TemplateMigration.text';
            $fileDirWrite = __DIR__ . "\..\..\database\migrations\\00_create_$tableName" . "_table.php";
            $modelStrings = ['//table' => $tableName];
            $this->replaceWriteTemplates($fileDirTemplate, $fileDirWrite, $modelStrings);

            $properties = $schema['properties'];
            $this->makeCols($properties, $tableName);
        }
    }

    private function makeCols(array $properties, String $tableName): void
    {

        $contentToMigrationFile = "\n";

        foreach ($properties as $key => $property) {

            $typeCol = $this->formatTypeParam($property['type']);
            $modelStrings = ['//type' => $typeCol, '//colName' => $key];

            if (isset($property['default'])) {
                $fileDirTemplate = __DIR__ . '\..\Templates\Migrations\TemplateColsDefault.text';
                $defaultValue = ['//default' => $property['default']];
                $modelStrings = array_merge($modelStrings, $defaultValue);
            } else {
                $fileDirTemplate = __DIR__ . '\..\Templates\Migrations\TemplateCols.text';
            }

            $contentToMigrationFile = $contentToMigrationFile . $this->replaceTemplates($fileDirTemplate, $modelStrings);
        }

        $fileDirWrite = __DIR__ .  "\..\..\database\migrations\\00_create_$tableName" . "_table.php";
        $modelStrings = ['//cols' => $contentToMigrationFile];
        $this->replaceWriteTemplates($fileDirWrite, $fileDirWrite, $modelStrings);
    }

}
