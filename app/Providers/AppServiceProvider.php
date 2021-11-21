<?php

namespace App\Providers;

use App\Console\Dialog\PersonDialog;
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
        $this->app->singleton(Database::class, function ($app) {
            $configuration = config('storage');
            return new Database($configuration['datadir'], $configuration['configuration']);
        });
        /* $this->app->singleton(PersonDialog::class, function ($app) { */
        /*     return new PersonDialog( */
        /*         $app->make(Database::class), */
        /*         $app->input, */
        /*         $app->output, */
        /*         $app->verbosity, */
        /*     ); */
        /* }); */
    }
}
