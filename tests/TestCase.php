<?php

namespace Tests;

use DarkGhostHunter\Larafind\Facades\Find;
use DarkGhostHunter\Larafind\LarafindServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LarafindServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Find' => Find::class
        ];
    }
}
