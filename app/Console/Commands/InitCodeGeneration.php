<?php

namespace App\Console\Commands;

use App\Actions\BehavioralGenerator;
use Illuminate\Console\Command;

class InitCodeGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:code {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize code generation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $behaviralGenerator = new BehavioralGenerator;
        $behaviralGenerator->initProcess($this->argument('path'));

        echo "---------------------------\nCódigo gerado com sucesso\n---------------------------";
    }
}
