<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 18.10.16
 * Time: 8:37
 */

namespace RonasIT\Support;

use Illuminate\Support\ServiceProvider;
use RonasIT\Support\Commands\MakeEntityCommand;

class HelpersServiceProvider extends ServiceProvider
{
    public function boot() {
        $this->commands([
            MakeEntityCommand::class
        ]);

        $this->publishes([
            __DIR__.'/../config/entity-generator.php' => config_path('entity-generator.php'),
        ], 'config');
    }

    public function register()
    {

    }
}