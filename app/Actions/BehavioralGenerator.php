<?php

namespace App\Actions;

use App\Http\Traits\DSLTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;


class BehavioralGenerator
{

    use DSLTrait;

    private $data;
    private $contentToRouteFile;

    public function initProcess()
    {
        $this->loadData();

        $this->makeModels();
        $this->makeControllers();
        $this->makeRoutes();
        $this->makeMigrations();
    }

    private function loadData(): void
    {
        $openApi = Storage::get('openapi_doc\events.yaml');
        $this->data = Yaml::parse($openApi);
    }

    private function makeModels() : void
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

    private function makeControllers(): void
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

    private function makeMethods(String $className): string
    {

        $paths = $this->data['paths'];
        $contentToControllerFile = "\n\n";
        $exportsToControllerFile = "\n";

        foreach ($paths as $path => $methods) {

            foreach ($methods as $methodContent) {

                $camelClassName = $this->prepareClassName($path);

                if($camelClassName == $className) {
                    [$contentToControllerFile, $exportsToControllerFile] = $this->makeBehavior($methodContent, $contentToControllerFile, $exportsToControllerFile);
                }

            }
        }

        $fileDirTemplate = __DIR__ . "\..\Http\Controllers\\$className" . "Controller.php";
        $modelStrings = ['//methods' => $contentToControllerFile, '//exports' => $exportsToControllerFile];
        $this->replaceWriteTemplates($fileDirTemplate, $fileDirTemplate, $modelStrings);

        return $contentToControllerFile;
    }

    private function makeBehavior(
        array $methodContent,
        String $contentToControllerFile,
        String $exportsToControllerFile
    ): array | false
    {

        $description = $methodContent['description'];

        if (!strpos($description, '<dsl>')) {
            return false;
        }

        $descriptionParts = explode('<dsl>', $description);
        $scripts = explode("\n", trim($descriptionParts[ count($descriptionParts) - 2]));

        $fileDirTemplateMethod = __DIR__ . '\..\Templates\Methods\TemplateMethod.text';
        $fileDirTemplateExport = __DIR__ . '\..\Templates\Controllers\TemplateExportModel.text';

        foreach ($scripts as $key => $script) {

            [$hasModel, $behavior, $modelName] = $this->checkModelExists($script, $methodContent);

            if ($hasModel) {
                $nameMethod = $this->snakeCaseToCamelCase($methodContent['operationId']);
                $modelStrings = ['//method' => $nameMethod, '//behavioral' => $behavior];
                $contentToControllerFile = $contentToControllerFile . $this->replaceTemplates($fileDirTemplateMethod, $modelStrings) . "\n";

                // Exportações
                $modelStrings = ['//model' => $modelName];
                $exportsToControllerFile = $exportsToControllerFile . $this->replaceTemplates($fileDirTemplateExport, $modelStrings) . "\n";
            }

            [$hasReturn, $return] = $this->checkReturnExists($script, $methodContent);
            if ($hasReturn) {
                $contentToControllerFile = str_replace('//return', $return, $contentToControllerFile);
            }




        }

        return [$contentToControllerFile, $exportsToControllerFile];
    }


    private function makeRoutes(): void
    {

        $paths = $this->data['paths'];

        foreach ($paths as $namePath => $path) {
            foreach ($path as $method => $pathContent) {

                $camelClassName = $this->prepareClassName($namePath);
                $functionName = $this->snakeCaseToCamelCase($pathContent['operationId']);

                $routeFileDir = __DIR__ . '\..\Templates\Routes\TemplateRoute.text';
                $routeFileContent = file_get_contents($routeFileDir);
                $routeFileContent = str_replace('//path', $namePath, $routeFileContent);
                $routeFileContent = str_replace('//Controller', $camelClassName . "Controller", $routeFileContent);
                $routeFileContent = str_replace('//method', $method, $routeFileContent);
                $routeFileContent = str_replace('//functionName', $functionName, $routeFileContent);

                $this->contentToRouteFile = $this->contentToRouteFile . $routeFileContent;
            }

        }

        $routeFileDir = __DIR__ .  "\..\..\\routes\web.php";
        $routeFileContent = file_get_contents($routeFileDir);
        $routeFileContent = str_replace('//routes', $this->contentToRouteFile, $routeFileContent);
        $file = fopen($routeFileDir, 'w');
        fwrite($file, $routeFileContent);
        fclose($file);
    }

    private function makeMigrations(): void
    {

        $schemas = $this->data['components']['schemas'];
        foreach ($schemas as $className => $schema) {

            $tableName = $this->camelCaseToSnakeCase($className);

            $properties = $schema['properties'];
            $migrationFileDir = __DIR__ . '\..\Templates\Migrations\TemplateMigration.text';
            $migrationFileContent = file_get_contents($migrationFileDir);
            $migrationFileContent = str_replace('//table', $tableName, $migrationFileContent);

            // $datetime = Carbon::now()->format('Y_m_d') . '_' . strtotime(Carbon::now()->format('H:i:s'));

            $fileDir = __DIR__ . "\..\..\database\migrations\create_$tableName" . "_table.php";
            $file = fopen($fileDir, 'w');
            fwrite($file, $migrationFileContent);
            fclose($file);

            $this->makeCols($properties, $tableName);
        }
    }

    private function makeCols(array $properties, String $tableName): void
    {

        $contentToMigrationFile = "\n";

        foreach ($properties as $key => $property) {
            $colFileDir = __DIR__ . '\..\Templates\Migrations\TemplateCols.text';
            $colFileContent = file_get_contents($colFileDir);
            $colFileContent = str_replace('//type', $property['type'], $colFileContent);
            $colFileContent = str_replace('//colName', $key, $colFileContent);

            $contentToMigrationFile = $contentToMigrationFile . $colFileContent;
        }

        $migrationFileDir = __DIR__ .  "\..\..\database\migrations\create_$tableName" . "_table.php";
        $migrationFileContent = file_get_contents($migrationFileDir);
        $migrationFileContent = str_replace('//cols', $contentToMigrationFile, $migrationFileContent);
        $file = fopen($migrationFileDir, 'w');
        fwrite($file, $migrationFileContent);
        fclose($file);
    }

    private function transformToSigular(String $attribute): string
    {
        return (preg_match('~s$~i', $attribute) > 0) ? rtrim($attribute, 's') : $attribute;
    }

    private function prepareClassName(String $namePath): string
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


    private function getComponentAttributes(String $modelName): string
    {

        $attributes = '';
        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {
            if($className == $modelName) {
                $properties = $schema['properties'];
                foreach ($properties as $attribute => $property) {
                    $request = '$request->' . $attribute;
                    $attributes = $attributes == '' ? "'$attribute' => $request \n" : "$attributes, '$attribute' => $request \n";
                }
                return $attributes;
            }
        }

        return $attributes;
    }

    private function getRequestAttributes(array $requestBody): string
    {

        $attributes = '';
        $properties = $requestBody['properties'];

        foreach ($properties as $attribute => $property) {
            $request = '$request->' . $attribute;
            $attributes = $attributes == '' ? "'$attribute' => $request" : "$attributes, \n            '$attribute' => $request";
        }

        return $attributes . "\n       ";

    }

    private function replaceWriteTemplates(String $fileDirTemplate, String $fileDirWrite, array $modelStrings): void
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

    private function replaceTemplates(String $fileDirTemplate, array $modelStrings): string
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
}
