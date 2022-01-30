<?php

namespace App\Providers;

use App\Persistence\Database;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(OutputInterface $output)
    {
        $formatter = $output->getFormatter();
        $formatter->setStyle('thead', new OutputFormatterStyle(foreground: 'yellow'));
        $formatter->setStyle('tgroup', new OutputFormatterStyle(foreground: 'green'));
        $formatter->setStyle('added', new OutputFormatterStyle(foreground: 'green'));
        $formatter->setStyle('deleted', new OutputFormatterStyle(foreground: 'red'));
        $formatter->setStyle('changed', new OutputFormatterStyle(foreground: 'yellow'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Database::class, function ($app) {
            return new Database($app, config('storage.datadir'), config('storage.configuration'));
        });
    }
}
