<?php

namespace Flooris\FilemakerApi;

use FileMakerApi;
use Illuminate\Support\ServiceProvider;

class LaravelServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/filemaker.php' => config_path('filemaker.php'),
        ]);
    }

    public function register()
    {
        $this->app->bind(FileMakerApi::class, function() {
            $fm_api = new FileMakerApi(
                NULL,
                config('filemaker.database'),
                config('filemaker.hostname'),
                config('filemaker.username'),
                config('filemaker.password'),
                (bool)config('app.debug')
            );

            return $fm_api;
        });
    }
}