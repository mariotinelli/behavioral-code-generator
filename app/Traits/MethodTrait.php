<?php

namespace App\Traits;


trait MethodTrait {

    use FunctionsTrait;

    protected function makeMethods(String $className): string
    {
        $paths = $this->data['paths'];

        $contentToControllerFile = "\n\n";
        $exportsToControllerFile = "\n";

        foreach ($paths as $path => $methods) {

            foreach ($methods as $httpMethod => $methodContent) {

                $camelClassName = $this->prepareClassName($path);
                $methodContent = array_merge($methodContent, ['camelClassName' => $camelClassName]);

                $method = isset($methodContent['parameters']) ? $this->makeMethodParam($methodContent) : $this->makeMethod($methodContent);

                [$contentToControllerFile, $exportsToControllerFile] = $this->makeBehavior($methodContent, $method, $httpMethod, $contentToControllerFile, $exportsToControllerFile);
            }
        }

        $fileDirTemplate = __DIR__ . "\..\Http\Controllers\\$className" . "Controller.php";
        $modelStrings = ['//methods' => $contentToControllerFile, '//exports' => $exportsToControllerFile];
        $this->replaceWriteTemplates($fileDirTemplate, $fileDirTemplate, $modelStrings);

        return $contentToControllerFile;
    }

    private function makeMethod(array $methodContent): string
    {
        $nameMethod = $this->snakeCaseToCamelCase($methodContent['operationId']);
        $fileDirTemplateMethod = __DIR__ . '\..\Templates\Methods\TemplateMethod.text';
        $modelStrings = ['//method' => $nameMethod];
        return $this->replaceTemplates($fileDirTemplateMethod, $modelStrings);
    }

    private function makeMethodParam(array $methodContent): string
    {
        $nameMethod = $this->snakeCaseToCamelCase($methodContent['operationId']);
        $fileDirTemplateMethod = __DIR__ . '\..\Templates\Methods\TemplateMethodParam.text';
        $modelStrings = ['//method' => $nameMethod];

        $typeParam = $this->formatTypeParam($methodContent['parameters'][0]['schema']['type']);

        if ($methodContent['parameters'][0]['in'] == 'path') {
            $newModelStrings = ['//type' => $typeParam, '//param' => $methodContent['parameters'][0]['name']];
            $modelStrings = array_merge($modelStrings, $newModelStrings);
        }

        return $this->replaceTemplates($fileDirTemplateMethod, $modelStrings);
    }

    private function makeBehavior(
        array $methodContent,
        String $method,
        String $httpMethod,
        String $contentToControllerFile,
        String $exportsToControllerFile
    ): array | false
    {

        $description = $methodContent['description'];

        if (!strpos($description, '<dsl>')) {
            $modelStrings = ['//behavioral' => $this->messageWithoutBehavior()];
            $method = $this->replaceStringTemplates($method, $modelStrings);
            $contentToControllerFile = $contentToControllerFile . $method . "\n";
            return [$contentToControllerFile, $exportsToControllerFile];
        }

        $descriptionParts = explode('<dsl>', $description);
        $scripts = explode("\n", trim($descriptionParts[ count($descriptionParts) - 2]));

        $fileDirTemplateExport = __DIR__ . '\..\Templates\Controllers\TemplateExportModel.text';

        foreach ($scripts as $script) {

            $regexVar = '/\$[a-z][a-zA-Z0-9]*( )*(=)( )*/';
            if ($this->checkExists($script, $regexVar)) {
                $varName = $this->varProcess($script, $regexVar);
            }
            $hasVar = isset($varName);
            $regexModel = '/Model\((.*?)\)/';
            if ($this->checkExists($script, $regexModel)) {

                [$behavior, $modelName] = $this->modelProcess($script);

                // Validation
                if(isset($methodContent['requestBody']['content']['application/json']['schema'])) {
                    $schema = $methodContent['requestBody']['content']['application/json']['schema'];
                    $validations = $this->makeValidation($schema, $httpMethod, $modelName);
                    $modelStrings = ['//behavioral' => "\n        " . $validations . "//behavioral"];
                    $method = $this->replaceStringTemplates($method, $modelStrings);

                }
                if ($hasVar) {
                    $fileDirTemplate = __DIR__ . '\..\Templates\Controllers\TemplateVar.text';
                    $modelStrings = ['//varName' => $varName, '//varValue' => $behavior];
                    $behavior = trim($this->replaceTemplates($fileDirTemplate, $modelStrings)) . "\n";
                }
                // Behavior
                $modelStrings = ['//behavioral' => "\n        " . $behavior . "//behavioral"];
                $method = $this->replaceStringTemplates($method, $modelStrings);

                // Exportações
                $modelStrings = ['//model' => $modelName];
                $fileDirTemplateExport = __DIR__ . '\..\Templates\Controllers\TemplateExportModel.text';
                $export = $this->replaceTemplates($fileDirTemplateExport, $modelStrings);
                if (strpos($exportsToControllerFile, trim($export)) === false) {
                    $exportsToControllerFile = $exportsToControllerFile . $export . "\n";
                }

            }
            $regexReturn = '/Return\((.*?)\)/';
            if ($this->checkExists($script, $regexReturn)) {
                $dataReturn = $this->returnProcess($script);
                $modelStrings = ['//behavioral' => "\n        " . $dataReturn . "//behavioral"];
                $method = $this->replaceStringTemplates($method, $modelStrings);
            }
        }
        $method = str_replace('//behavioral', '', $method);
        $contentToControllerFile = $contentToControllerFile . $method . "\n";

        return [$contentToControllerFile, trim($exportsToControllerFile)];
    }


    private function messageWithoutBehavior() {
        $message = '
        /*
            Método sem comportamento...
            Você pode especificar qual será o comportamento do método, inserindo um script na documentação de sua api.
            Esse script precisa ser separado pela tag <dsl>Seu script aqui<dsl> e precisa ser inserido no campo "description" de cada path.

            Um exemplo: \n
                delete:
                    description: |
                        Deletar um evento pelo seu id

                        <dsl>
                            Seu script aqui
                        <dsl>

            Como é o script:
                Manipulação de dados: Model(ModelName)->httpMethod();
                    + O parâmetro ModelName, é o nome do modelo que será utilizado para inserir os dados;
                    + O httpMethod é o método http que será utilizado para essa manipulação (post, put, get, patch e delete);
                    + Ex para inserção de dados: Model(Event)->post();
                Atribuição de dados: $event = (...);
                    + A variavel precisa ser iniciada com "$" para determinar que aquilo é uma variável do sistema.
                    + Ex: $event = Model(Event)->get()->first();
                Retorno de dados: Return(content, httpCode);
                    + O parâmetro content, é o conteúdo que será retornado pela api seja ele uma string ou um recurso qualquer;
                    + O httpCode é o código de retorno.
                    + Ex: Return("Evento criado com sucesso", 200);
        */
        ';

        return $message;
    }
}
