<?php

namespace App\Actions;

use App\Traits\ControllerTrait;
use App\Traits\DSLTrait;
use App\Traits\MigrationTrait;
use App\Traits\ModelTrait;
use App\Traits\RouteTrait;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;


class BehavioralGenerator
{

    use DSLTrait, ModelTrait, ControllerTrait, RouteTrait, MigrationTrait;

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
        $openApi = Storage::get('openapi\events.yaml');
        $this->data = Yaml::parse($openApi);
    }

}
