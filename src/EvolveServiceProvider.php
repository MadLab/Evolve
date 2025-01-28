<?php

namespace MadLab\Evolve;


use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use MadLab\Evolve\Models\Evolve;

class EvolveServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'evolve');
    }

    public function boot(HttpKernel $kernel)
    {
        $this->defineGate();
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('evolve.php'),
            ], 'evolve-config');

        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'evolve');

        $this->app->singleton('experiments', function ($app) {
            return Evolve::where('is_active', true)->get();
        });

        Blade::component(\MadLab\Evolve\Components\Evolve::class, 'evolve');


    }

    protected function defineGate()
    {
        Gate::define('viewEvolveAdminPanel', function ($user) {
            return in_array($user->email, config('evolve.admin_emails', []));
        });
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
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
