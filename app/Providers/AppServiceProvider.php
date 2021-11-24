<?php

namespace App\Providers;

use App\Configuration;
use App\Database;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Database::class, function ($app) {
            $configuration = config('storage');
            return new Database($configuration['datadir'], $configuration['configuration']);
        });
        $this->app->singleton(Configuration::class);

        /* $this->app->bind(OutputStyle::class, \App\Console\OutputStyle::class); */
    }
}
