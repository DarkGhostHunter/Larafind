<?php

namespace DarkGhostHunter\Larafind;

use Composer\Autoload\ClassLoader;
use Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Finder\Finder as BaseFinder;
use Symfony\Component\Finder\SplFileInfo;

class Finder
{
    /**
     * Path to discover.
     *
     * @var string
     */
    protected string $path;

    /**
     * If the exploring should expand to child directories.
     *
     * @var bool
     */
    protected bool $recursive = true;

    /**
     * Filters to add to the exploring.
     *
     * @var array|\Closure[]
     */
    protected array $filters = [];

    /**
     * Finder constructor.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Symfony\Component\Finder\Finder  $finder
     */
    public function __construct(protected Application $app, protected BaseFinder $finder)
    {
        $this->path = $this->app->path();
    }

    /**
     * Sets the application paths in the Finder.
     *
     * @param  string  $path
     *
     * @return $this
     */
    public function path(string $path): static
    {
        $this->path = $this->app->path($path);

        return $this;
    }

    /**
     * Sets the project paths from the root in the Finder.
     *
     * @param  string  $path
     *
     * @return $this
     */
    public function basePath(string $path): static
    {
        $this->path = $this->parsePath($path);

        return $this;
    }

    /**
     * Parses a path from the project base path.
     *
     * @param  string  $path
     *
     * @return string
     */
    protected function parsePath(string $path): string
    {
        $psr4 = static::getPsr4Paths();

        if (! Str::startsWith(trim($path, '/'), $this->app->path()) && ! $this->isAutoloaded($psr4, $path)) {
            throw new RuntimeException("The files in [$path] are not registered for class autoloading.");
        }

        return $this->app->basePath($path);
    }

    /**
     * Check if the path is autoloaded.
     *
     * @param  array  $psr4
     * @param  string  $path
     *
     * @return bool
     */
    protected function isAutoloaded(array $psr4, string $path): bool
    {
        foreach ($psr4 as $autoloaded) {
            if (Str::endsWith($autoloaded, Str::of($path)->before(DIRECTORY_SEPARATOR)->before('/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the array of registered PSR-4 paths.
     *
     * @return array
     */
    protected static function getPsr4Paths(): array
    {
        $registered = [];
        $i = 0;

        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $paths) {
                foreach ($paths as $path) {
                    $registered[realpath($path)] = $i;
                    ++$i;
                }
            }
        }

        return array_flip($registered);
    }

    /**
     * Sets the exploration as recursive or non-recursive.
     *
     * @param  bool  $recursive
     *
     * @return $this
     */
    public function recursive(bool $recursive = true): static
    {
        $this->recursive = $recursive;

        return $this;
    }

    /**
     * Sets the exploration as non-recursive.
     *
     * @return $this
     */
    public function nonRecursive(): static
    {
        return $this->recursive(false);
    }

    /**
     * Filters all classes by their implementing interfaces.
     *
     * @param  string  ...$interfaces
     *
     * @return $this
     */
    public function implementing(string ...$interfaces): static
    {
        $this->filters['implementing'] = static function (ReflectionClass $class) use ($interfaces): bool {
            return !count(array_diff($interfaces, $class->getInterfaceNames()));
        };

        return $this;
    }

    /**
     * Filters all classes by their extending class.
     *
     * @param  string  $name
     *
     * @return $this
     */
    public function extending(string $name): static
    {
        $this->filters['extending'] = static function (ReflectionClass $class) use ($name): bool {
            return $class->isSubclassOf($name);
        };

        return $this;
    }

    /**
     * Filters all classes by their used traits.
     *
     * @param  string  ...$traits
     *
     * @return $this
     */
    public function using(string ...$traits): static
    {
        $this->filters['using'] = static function (ReflectionClass $class) use ($traits): bool {
            foreach ($traits as $trait) {
                if (! in_array($trait, trait_uses_recursive($class->getName()), true)) {
                    return false;
                }
            }

            return true;
        };

        return $this;
    }

    /**
     * Filters all classes by their public methods.
     *
     * @param  string  ...$methods
     *
     * @return $this
     */
    public function methods(string ...$methods): static
    {
        $this->filters['methods'] = static function (ReflectionClass $class) use ($methods): bool {
            foreach ($methods as $method) {
                if (!$class->hasMethod($method) || ! $class->getMethod($method)->isPublic()) {
                    return false;
                }
            }

            return true;
        };

        return $this;
    }

    /**
     * Filters all classes by their public properties.
     *
     * @param  string  ...$properties
     *
     * @return $this
     */
    public function properties(string ...$properties): static
    {
        $this->filters['properties'] = static function (ReflectionClass $class) use ($properties): bool {
            foreach ($properties as $property) {
                if (!$class->hasProperty($property) || !$class->getProperty($property)->isPublic()) {
                    return false;
                }
            }

            return true;
        };

        return $this;
    }

    /**
     * Discover all classes from the paths.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get(): Collection
    {
        $files = iterator_to_array($this->discoverFiles());

        foreach ($this->filters as $filter) {
            $files = array_filter($files, $filter, ARRAY_FILTER_USE_BOTH);
        }

        return new Collection($files);
    }

    /**
     * Discovers all the files in all the set paths.
     *
     * @return \Generator<\ReflectionClass>
     */
    protected function discoverFiles(): Generator
    {
        $files = $this->finder->files()->in($this->path)->filter(
            static fn(SplFileInfo $file): bool => $file->getExtension() === 'php'
        );

        if (!$this->recursive) {
            $files->depth(0);
        }

        foreach ($files as $file) {
            if ($reflection = $this->getReflection($file)) {
                yield $reflection->name => $reflection;
            }
        }
    }

    /**
     * Parses the discovered file and as a class.
     *
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     *
     * @return \ReflectionClass|null
     */
    protected function getReflection(SplFileInfo $file): ?ReflectionClass
    {
        $classname = str_replace(
            DIRECTORY_SEPARATOR,
            '\\',
            ucfirst(
                Str::replaceLast(
                    '.php',
                    '',
                    trim(Str::replaceFirst($this->app->basePath(), '', $file->getRealPath()), DIRECTORY_SEPARATOR)
                )
            )
        );

        try {
            $class = new ReflectionClass($classname);
        } catch (ReflectionException) {
            return null;
        }

        return $class->isInstantiable()
            ? $class
            : null;
    }
}
