<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Scriptotek\Alma\Client as AlmaClient;

class AlmaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AlmaClient::class, function ($app) {
            $client = new AlmaClient(config('services.alma.key'), config('services.alma.region'));
            $client->nz->setKey(config('services.alma.nz_key'));
            return $client;
        });
    }
}
