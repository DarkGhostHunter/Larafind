<?php

namespace DarkGhostHunter\Larafind;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class LarafindServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            Finder::class,
            static fn (Application $app): Finder => new Finder($app, new SymfonyFinder(), new Collection())
        );
    }
}
