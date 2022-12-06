<?php

namespace App\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;

use function PHPUnit\Framework\matches;

class BehavioralGenerator
{
    private $data;
    private $contentToRouteFile;

    public function initProcess() {
        $this->getData();

        $this->makeModels();
        $this->makeControllers();
        $this->makeRoutes();
        $this->makeMigrations();
    }

    private function getData() {
        $openApi = Storage::get('openapi_doc\events.yaml');
        $openApiParse = Yaml::parse($openApi);

        $this->data = $openApiParse;
    }

    private function makeModels() {

        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {
            $fillable = 'protected $fillable = [';
            $properties = $schema['properties'];
            foreach ($properties as $key => $property) {
                $fillable = "$fillable '$key', ";
            }
            $fillable = "$fillable]";

            $tableName = $this->camelCaseToSnakeCase($className);

            // Models
            $modelFileDir = __DIR__ . '\..\Templates\Models\TemplateModel.text';
            $modelFileContent = file_get_contents($modelFileDir);
            $modelFileContent = str_replace('//fillable', $fillable, $modelFileContent);
            $modelFileContent = str_replace('//ClassName', $className, $modelFileContent);
            $modelFileContent = str_replace('//table', $tableName, $modelFileContent);

            $fileDir = __DIR__ . "\..\Models\\$className.php";
            $file = fopen($fileDir, 'w');
            fwrite($file, $modelFileContent);
            fclose($file);

        }
    }

    private function makeControllers() {

        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {
            $controllerFileDir = __DIR__ . '\..\Templates\Controllers\TemplateController.text';
            $controllerFileContent = file_get_contents($controllerFileDir);
            $controllerFileContent = str_replace('//ClassName', $className . 'Controller', $controllerFileContent);

            $fileDir = __DIR__ . "\..\Http\Controllers\\$className" . "Controller.php";
            $file = fopen($fileDir, 'w');
            fwrite($file, $controllerFileContent);
            fclose($file);

            $this->makeMethods($className);
        }
    }

    private function makeMethods($className) {

        $paths = $this->data['paths'];
        $contentToControllerFile = "\n\n";
        $exportsToControllerFile = "\n";
        foreach ($paths as $route => $path) {
            foreach ($path as $method => $pathContent) {
                $camelClassName = $this->prepareClassName($route);
                if($camelClassName == $className) {

                    $methodFileDir = __DIR__ . '\..\Templates\Methods\TemplateMethod.text';
                    $methodFileContent = file_get_contents($methodFileDir);
                    $methodFileContent = str_replace('//method', $method, $methodFileContent);

                    [$behavior, $modelName] = $this->makeBehavior($pathContent);
                    $methodFileContent = str_replace('//behavioral', $behavior, $methodFileContent);

                    $contentToControllerFile = $contentToControllerFile . $methodFileContent . "\n";


                    $exportFileDir = __DIR__ . '\..\Templates\Controllers\TemplateExportModel.text';
                    $exportFileContent = file_get_contents($exportFileDir);
                    $exportFileContent = str_replace('//model', $modelName, $exportFileContent);
                    $exportsToControllerFile = $exportsToControllerFile . $exportFileContent . "\n";

                }

            }
        }

        $fileDir = __DIR__ . "\..\Http\Controllers\\$className" . "Controller.php";
        $methodFileContent = file_get_contents($fileDir);
        $methodFileContent = str_replace('//methods', $contentToControllerFile, $methodFileContent);
        $methodFileContent = str_replace('//exports', $exportsToControllerFile, $methodFileContent);
        $file = fopen($fileDir, 'w');
        fwrite($file, $methodFileContent);
        fclose($file);

        return $contentToControllerFile;
    }

    private function DSLModel($script, $pathContent) {
        $result = preg_match_all("/Model\((.*?)\)/", $script, $matches);
        if ($result) {
            $modelName = $matches[1][0];
            return $this->DSLMethod($script, $modelName, $pathContent);
        }
        return false;
    }


    private function DSLMethod($script, $modelName, $pathContent) {

        $hasPost = preg_match_all("/post\((.*?)\)/", $script, $matches);
        if ($hasPost) {
            $requestBody = $pathContent['requestBody']['content']['application/json']['schema'];
            $attributes = isset($requestBody['$ref']) ? $this->getComponentAttributes($modelName) : $this->getRequestAttributes($requestBody);

            // Create
            $createFileDir = __DIR__ . '\..\Templates\Controllers\TemplateCreate.text';
            $fileContent = file_get_contents($createFileDir);
            $fileContent = str_replace('//model', $modelName, $fileContent);
            $fileContent = str_replace('//attributes', $attributes, $fileContent);

            return [$fileContent, $modelName];
        }

        $hasGet = preg_match_all("/get\((.*?)\)/", $script, $matches);
        if ($hasPost) {
            return 'get';
        }

        $hasUpdate = preg_match_all("/update\((.*?)\)/", $script, $matches);
        if ($hasPost) {
            return 'update';
        }

        $hasPatch = preg_match_all("/patch\((.*?)\)/", $script, $matches);
        if ($hasPost) {
            return 'patch';
        }

        $hasDelete = preg_match_all("/delete\((.*?)\)/", $script, $matches);
        if ($hasPost) {
            return 'delete';
        }

        return false;
    }


    private function makeBehavior($pathContent) {

        $description = $pathContent['description'];
        $descriptionParts = explode('%', $description);

        $scripts = explode("\n", trim($descriptionParts[ count($descriptionParts) - 2]));

        foreach ($scripts as $script) {
            [$fileContent, $modelName] = $this->DSLModel($script, $pathContent);
        }

        return [$fileContent, $modelName];
    }


    private function makeRoutes() {

        $paths = $this->data['paths'];

        foreach ($paths as $namePath => $path) {
            foreach ($path as $method => $pathContent) {

                $camelClassName = $this->prepareClassName($namePath);

                $routeFileDir = __DIR__ . '\..\Templates\Routes\TemplateRoute.text';
                $routeFileContent = file_get_contents($routeFileDir);
                $routeFileContent = str_replace('//path', $namePath, $routeFileContent);
                $routeFileContent = str_replace('//Controller', $camelClassName . "Controller", $routeFileContent);
                $routeFileContent = str_replace('//method', $method, $routeFileContent);

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

    private function makeMigrations() {

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

    private function makeCols($properties, $tableName){

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

    private function transformToSigular($attribute) {
        return (preg_match('~s$~i', $attribute) > 0) ? rtrim($attribute, 's') : $attribute;
    }

    private function prepareClassName($namePath){

        $explodedRoutes = explode('/', $namePath);
        $originalClassName = $this->transformToSigular($explodedRoutes[0] == '' ? $explodedRoutes[1] : $$explodedRoutes[0]);

        $camelClassName = str_replace('_', ' ', $originalClassName);
        $camelClassName = str_replace('-', ' ', $camelClassName);

        $camelClassName = $this->transformToSigular(ucwords($camelClassName));

        return str_replace(' ', '', $camelClassName);
    }

    function camelCaseToSnakeCase($string)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    function snakeCaseToCamelCase($string)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }


    private function getComponentAttributes($modelName) {

        $attributes = '';
        $schemas = $this->data['components']['schemas'];

        foreach ($schemas as $className => $schema) {
            if($className == $modelName) {
                $properties = $schema['properties'];
                foreach ($properties as $attribute => $property) {
                    $attributes = $attributes == '' ? "['$attribute'" : "$attributes, '$attribute'";
                }
                return $attributes . "]";
            }
        }

        return $attributes;
    }

    private function getRequestAttributes($requestBody) {

        $attributes = '';
        $properties = $requestBody['properties'];

        foreach ($properties as $attribute => $property) {
            $attributes = $attributes == '' ? "['$attribute'" : "$attributes, '$attribute'";
        }
        return $attributes . "]";

    }
}
