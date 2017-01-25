<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 8:46
 */

namespace RonasIT\Support\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RonasIT\Support\Events\SuccessCreateMessage;
use RonasIT\Support\Generators\ControllerGenerator;
use RonasIT\Support\Generators\MigrationsGenerator;
use RonasIT\Support\Generators\ModelGenerator;
use RonasIT\Support\Generators\RepositoryGenerator;
use RonasIT\Support\Generators\RequestsGenerator;
use RonasIT\Support\Generators\ServiceGenerator;
use RonasIT\Support\Generators\TestsGenerator;
use RonasIT\Support\Services\ClassGeneratorService;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

/**
 * @property ControllerGenerator $controllerGenerator
 * @property MigrationsGenerator $migrationsGenerator
 * @property ModelGenerator $modelGenerator
 * @property RepositoryGenerator $repositoryGenerator
 * @property RequestsGenerator $requestsGenerator
 * @property ServiceGenerator $serviceGenerator
 * @property TestsGenerator $testGenerator
 * @property EventDispatcher $eventDispatcher
*/
class MakeEntityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:entity {name : The name of the entity. This name will use as name of models class. }
        {--without-model : Set this flag if you already have model for this entity. Command will find it. This flag is a lower priority than --only-model} 
        {--without-repository : Set if you don\'t want to use Data Access Level. Created Service will use special trait for controlling entity. This flag is a lower priority than --without-repository} 
        {--without-service : Set this flag if you don\'t want to create service} 
        {--without-controller : Set this flag if you don\'t want to create controller. Automatically requests will not create too.} 
        {--without-migrations : Set this flag if you already have table on db. This flag is a lower priority than --only-migrations}
        {--without-requests : Set this flag if you don\'t want to create requests to you controller}
        {--without-tests : Set this flag if you don\'t want to create tests. This flag is a lower priority than --only-tests}
        
        {--only-model : Set this flag if you want to create only model. This flag is a higher priority than --without-model, --only-migrations, --only-tests and --only-repository} 
        {--only-repository : Set this flag if you want to create only repository. This flag is a higher priority than --without-repository, --only-tests and --only-migrations}
        {--only-migrations : Set this flag if you want to create only repository. This flag is a higher priority than --without-migrations and --only-tests}
        {--only-tests : Set this flag if you want to create only tests. This flag is a higher priority than --without-tests}
        
        {--i|integer=* : Add integer field to entity}
        {--I|integer-required=* : Add required integer field to entity. If you want to specify default value you have to do it manually.}
        {--f|float=* : Add float field to entity}
        {--F|float-required=* : Add required float field to entity. If you want to specify default value you have to do it manually.}
        {--s|string=* : Add string field to entity. Default type is VARCHAR(255) but you can change it manually in migration}
        {--S|string-required=* : Add required string field to entity. If you want to specify default value ir size you have to do it manually.}
        {--b|boolean=* : Add boolean field to entity.}
        {--B|boolean-required=* : Add boolean field to entity. If you want to specify default value you have to do it manually.}
        {--t|timestamp=* : Add boolean field to entity. }
        {--T|timestamp-required=* : Add boolean field to entity. If you want to specify default value you have to do it manually.}
        
        {--a|has-one=* : Set hasOne relations between you entity and existed entity. }
        {--A|has-many=* : Set hasMany relations between you entity and existed entity. }
        {--e|belongs-to=* : Set belongsTo relations between you entity and existed entity. }
        {--E|belongs-to-many=* : Set belongsToMany relations between you entity and existed entity. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make entity with Model, Repository, Service, Migration and Controller.';

    protected $controllerGenerator;
    protected $migrationsGenerator;
    protected $modelGenerator;
    protected $repositoryGenerator;
    protected $requestsGenerator;
    protected $serviceGenerator;
    protected $testGenerator;
    protected $eventDispatcher;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->controllerGenerator = app(ControllerGenerator::class);
        $this->migrationsGenerator = app(MigrationsGenerator::class);
        $this->modelGenerator = app(ModelGenerator::class);
        $this->repositoryGenerator = app(RepositoryGenerator::class);
        $this->requestsGenerator = app(RequestsGenerator::class);
        $this->serviceGenerator = app(ServiceGenerator::class);
        $this->testGenerator = app(TestsGenerator::class);
        $this->eventDispatcher = app(EventDispatcher::class);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->eventDispatcher->listen(SuccessCreateMessage::class, $this->getOutputCallback());

        if ($this->option('only-model')) {
            $this->generateModel();

            return;
        }

        if ($this->option('only-repository')) {
            $this->generateRepository();

            return;
        }

        if ($this->option('only-migrations')) {
            $this->generateMigrations();

            return;
        }

        if ($this->option('only-tests')) {
            $this->generateTests();

            return;
        }

        if (!$this->option('without-model')) {
            $this->generateModel();
        }

        if (!$this->option('without-repository')) {
            $this->generateRepository();
        }

        if (!$this->option('without-migrations')) {
            $this->generateMigrations();
        }

        if (!$this->option('without-service')) {
            $this->generateService();
        }

        if (!$this->option('without-controller')) {
            if (!$this->option('without-requests')) {
                $this->generateRequests();
            }

            $this->generateController();
        }

        if (!$this->option('without-tests')) {
            $this->generateTests();
        }
    }

    protected function generateModel() {
        $fieldOptions = array_only($this->options(), [
            'integer', 'integer-required', 'string-required', 'string', 'float-required', 'float',
            'boolean-required', 'boolean', 'timestamp-required', 'timestamp'
        ]);

        $this->modelGenerator
            ->setName($this->argument('name'))
            ->setFields(array_collapse($fieldOptions))
            ->setRelations($this->getRelations())
            ->generate();
    }

    protected function generateRepository() {
        $this->repositoryGenerator
            ->setModel($this->argument('name'))
            ->generate();
    }

    protected function generateMigrations() {
        $fields = array_only($this->options(), [
            'integer', 'integer-required', 'string-required', 'string', 'float-required', 'float',
            'boolean-required', 'boolean', 'timestamp-required', 'timestamp'
        ]);

        $this->migrationsGenerator
            ->setName($this->argument('name'))
            ->setFields($fields)
            ->setRelations($this->getRelations())
            ->generate();
    }

    protected function generateService() {
        $this->serviceGenerator
            ->setModel($this->argument('name'))
            ->generate();
    }

    protected function generateController() {
        $this->controllerGenerator
            ->setModel($this->argument('name'))
            ->generate();
    }

    protected function generateRequests() {
        $fields = array_only($this->options(), [
            'integer', 'integer-required', 'string-required', 'string', 'float-required', 'float',
            'boolean-required', 'boolean', 'timestamp-required', 'timestamp'
        ]);

        $fieldsFromRelations = $this->option('belongs-to');

        foreach ($fieldsFromRelations as $field) {
            $fields['belongsTo'][] = $field;
        }

        $this->requestsGenerator
            ->setModel($this->argument('name'))
            ->setFields($fields)
            ->generate();
    }

    protected function generateTests() {
        $fields = array_only($this->options(), [
            'integer', 'integer-required', 'string-required', 'string', 'float-required', 'float',
            'boolean-required', 'boolean', 'timestamp-required', 'timestamp'
        ]);

        $this->testGenerator
            ->setModel($this->argument('name'))
            ->setFields($fields)
            ->setRelations($this->getRelations())
            ->generate();
    }

    protected function getRelations() {
        return [
            'hasOne' => $this->option('has-one'),
            'hasMany' => $this->option('has-many'),
            'belongsTo' => $this->option('belongs-to'),
            'belongsToMany' => $this->option('belongs-to-many')
        ];
    }

    protected function getOutputCallback() {
        return function (SuccessCreateMessage $event) {
            $this->info($event->message);
        };
    }
}

