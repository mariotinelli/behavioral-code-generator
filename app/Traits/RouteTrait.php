<?php

namespace App\Traits;


trait RouteTrait {

    use FunctionsTrait;

    protected function makeRoutes(): void
    {

        $paths = $this->data['paths'];
        foreach ($paths as $namePath => $path) {
            foreach ($path as $method => $pathContent) {

                $camelClassName = $this->prepareClassName($namePath);
                $functionName = $this->snakeCaseToCamelCase($pathContent['operationId']);

                $modelStrings = [
                    '//path' => $namePath,
                    '//Controller' => $camelClassName . "Controller",
                    '//method' => $method,
                    '//functionName' => $functionName,
                ];
                $routeFileDir = __DIR__ . '\..\Templates\Routes\TemplateRoute.text';
                $this->contentToRouteFile = $this->contentToRouteFile . $this->replaceTemplates($routeFileDir, $modelStrings);

            }

        }

        $routeFileDir = __DIR__ .  "\..\..\\routes\web.php";
        $modelStrings = ['//routes' => $this->contentToRouteFile];
        $this->replaceWriteTemplates($routeFileDir, $routeFileDir, $modelStrings);
    }

}
