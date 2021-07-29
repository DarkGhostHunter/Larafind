<?php

namespace Tests;

use DarkGhostHunter\Larafind\Finder;

class ServiceProviderTest extends TestCase
{
    public function test_register_finder(): void
    {
        static::assertInstanceOf(Finder::class, $this->app[Finder::class]);
    }

    public function test_registers_facade(): void
    {
        static::assertInstanceOf(Finder::class,\Find::getFacadeRoot());
    }
}
