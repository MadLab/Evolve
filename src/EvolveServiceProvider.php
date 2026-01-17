<?php

namespace MadLab\Evolve;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use MadLab\Evolve\Models\Evolve;
use MadLab\Evolve\Services\BotDetector;

class EvolveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'evolve');

        $this->app->singleton(BotDetector::class, fn () => new BotDetector);
    }

    public function boot(): void
    {
        $this->defineGate();
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('evolve.php'),
            ], 'evolve-config');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'evolve');

        $this->app->singleton('experiments', fn () => Evolve::where('is_active', true)->get());

        Blade::component(\MadLab\Evolve\Components\Evolve::class, 'evolve');
        Inertia::setRootView('evolve::app');
    }

    protected function defineGate(): void
    {
        Gate::define('viewEvolveAdminPanel', fn ($user) => in_array($user->email, config('evolve.admin_emails', [])));
    }

    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('evolve.prefix'),
            'middleware' => config('evolve.middleware', ['web']),
        ];
    }
}
