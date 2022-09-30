<?php

namespace Yarm\Adminnames;

use Illuminate\Support\ServiceProvider;

class AdminNamesServiceProvider extends ServiceProvider{

    public function boot()
    {

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/views','adminnames');
        //$this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->mergeConfigFrom(__DIR__ . '/config/AdminNames.php','adminnames');
        $this->publishes([
            //__DIR__ . '/config/bookshelf.php' => config_path('bookshelf.php'),
            //__DIR__ . '/views' => resource_path('views/vendor/adminkeywords'),
            // Assets
            __DIR__ . '/js' => resource_path('js/vendor'),
        ],'adminnames');


        //after every update
        //run   php artisan vendor:publish --provider="Yarm\Adminnames\AdminNamesServiceProvider" --tag="adminnames" --force
    }

    public function register()
    {

    }

}
