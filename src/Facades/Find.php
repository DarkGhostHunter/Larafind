<?php

namespace DarkGhostHunter\Larafind\Facades;

use DarkGhostHunter\Larafind\Finder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \DarkGhostHunter\Larafind\Finder path(string $path)
 * @method static \DarkGhostHunter\Larafind\Finder basePath(string $path)
 * @method static \DarkGhostHunter\Larafind\Finder recursive(bool $recursive = true)
 * @method static \DarkGhostHunter\Larafind\Finder nonRecursive()
 * @method static \DarkGhostHunter\Larafind\Finder implementing(string ...$interfaces)
 * @method static \DarkGhostHunter\Larafind\Finder extending(string $name)
 * @method static \DarkGhostHunter\Larafind\Finder using(string ...$traits)
 * @method static \DarkGhostHunter\Larafind\Finder methods(string ...$methods)
 * @method static \DarkGhostHunter\Larafind\Finder properties(string ...$properties)
 * @method static \Illuminate\Support\Collection|\ReflectionClass[] get()
 */
class Find extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return Finder::class;
    }
}
