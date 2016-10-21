<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 10:30
 */

return [
    'paths' => [
        'models' => 'app/Models',
        'services' => 'app/Services',
        'requests' => 'app/Http/Requests',
        'controllers' => 'app/Http/Controllers',
        'migrations' => 'database/migrations',
        'repositories' => 'app/Repositories',
        'tests' => 'tests',
        'routes' => 'routes/api.php',
        'factory' => 'database/factories/ModelFactory.php'
     ],
    'stubs' => [
        'model' => stubs_path('model.stub'),
        'relation' => stubs_path('relation.stub'),
        'repository' => stubs_path('repository.stub'),
        'service' => stubs_path('service.stub'),
        'service_with_trait' => stubs_path('service_with_trait.stub'),
        'controller' => stubs_path('controller.stub'),
        'request' => stubs_path('request.stub'),
        'validation_parameter' => stubs_path('validation_parameter.stub'),
        'routes' => stubs_path('routes.stub'),
        'use_routes' => stubs_path('use_routes.stub'),
        'migrations' => [
            'create' => stubs_path('migrations/create.stub'),
            'foreign_key' => stubs_path('migrations/foreign_key.stub'),
            'add_field' => stubs_path('migrations/add_field.stub'),
            'field' => stubs_path('migrations/field.stub')
        ],
        'tests' => [
            'test' => stubs_path('tests/test.stub'),
            'dump' => stubs_path('tests/dump.stub'),
            'factory' => stubs_path('tests/factory.stub'),
            'wrong_parameter_test' => stubs_path('tests/wrong_parameter_test.stub'),
            'insert' => stubs_path('tests/insert.stub'),
        ],
    ]
];