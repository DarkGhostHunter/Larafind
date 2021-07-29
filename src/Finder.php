<?php

namespace DarkGhostHunter\Larafind;

use Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;

class Finder
{
    /**
     * The paths to explore for classes.
     *
     * @var array|string[]
     */
    protected array $paths;

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
    public function __construct(protected Application $app, protected SymfonyFinder $finder)
    {
        $this->paths = [$this->app->path()];
    }

    /**
     * Sets the directories to explore.
     *
     * @param  string  ...$dir
     *
     * @return $this
     */
    public function dir(string ...$dir): static
    {
        $this->paths = [];

        return $this->addDir(...$dir);
    }

    /**
     * Adds one or many paths to the exploring process.
     *
     * @param  string  ...$dir
     *
     * @return $this
     */
    public function addDir(string ...$dir): static
    {
        $this->paths = array_merge($this->paths, $this->parsePaths($dir));

        return $this;
    }

    /**
     * Parses a path to the application base path.
     *
     * @param  array  $paths
     *
     * @return array|string[]
     */
    protected function parsePaths(array $paths): array
    {
        return array_map([$this->app, 'basePath'], $paths);
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
        $files = $this->finder->files()
            ->in($this->paths)
            ->filter(static fn(SplFileInfo $file): bool => $file->getExtension() === 'php');

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
