<?php

namespace App\Providers;

use App\Configuration;
use App\Database;
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
        $this->app->singleton(Database::class, function () {
            $configuration = config('storage');
            return new Database($configuration['datadir'], $configuration['configuration']);
        });
        $this->app->singleton(Configuration::class, function ($app) {
            return new Configuration($app);
        });
    }
}
