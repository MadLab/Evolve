<?php

namespace MadLab\Evolve;


use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MadLab\Evolve\Http\Middleware\ControllerVersion;
use MadLab\Evolve\Models\Experiment;

class EvolveServiceProvider extends ServiceProvider
{
    public function register()
    {

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'evolve');

        $this->app->bind('view.finder', function ($app) {
            return new FileViewFinder($app['files'], $app['config']['view.paths']);
        });
    }

    public function boot(HttpKernel $kernel)
    {

        if ($this->app->runningInConsole()) {
            \Log::info('Publishing resources in EvolveServiceProvider...');

            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('evolve.php'),
            ], 'evolve-config');

        }
        $this->registerRoutes();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'evolve');

        $kernel->prependMiddlewareToGroup('web',  ControllerVersion::class);

        $this->app->singleton('experiments', function($app) {
            return Experiment::where('is_active', true)->get();
        });
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('evolve.prefix'),
            'middleware' => config('evolve.middleware', ['web']),
        ];
    }
}
