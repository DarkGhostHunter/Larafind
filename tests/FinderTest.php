<?php

namespace Tests;

use ArrayAccess;
use DarkGhostHunter\Larafind\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Serializable;

class FinderTest extends TestCase
{
    /** @var string */
    protected static string $appPath;

    /**
     * @var mixed|object
     */
    protected Finder $finder;

    protected function setUp(): void
    {
        parent::setUp();

        static::$appPath = $this->app->path();

        $filesystem = new Filesystem();

        if (! $filesystem->exists($this->app->path('Discoverable'))) {
            $filesystem->copyDirectory(__DIR__.'/../stubs', $this->app->path('Discoverable'));

            require_once $this->app->path('Discoverable/Traits.php');
            require_once $this->app->path('Discoverable/Subdirectory/Traits.php');

            foreach (
                array_merge(
                    glob($this->app->path('Discoverable/*.php')),
                    glob($this->app->path('Discoverable/Subdirectory/*.php')),
                ) as $file) {
                require_once $file;
            }
        }

        $this->finder = $this->app[Finder::class];
    }

    public function test_finds_all_files_in_app_by_default(): void
    {
        $files = $this->finder->get();

        static::assertCount(8, $files);
        static::assertTrue($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Extending'));
        static::assertTrue($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Implementing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Implementing'));
    }

    public function test_changes_directory(): void
    {
        $files = $this->finder->dir('app/Discoverable/Subdirectory')->get();

        static::assertCount(4, $files);
        static::assertFalse($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Extending'));
        static::assertFalse($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\NormalClass'));
        static::assertFalse($files->has('App\Discoverable\Implementing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Implementing'));
    }

    public function test_adds_directory(): void
    {
        $files = $this->finder->nonRecursive()
            ->dir('app/Discoverable')
            ->addDir('app/Discoverable/Subdirectory')->get();

        static::assertCount(8, $files);
        static::assertTrue($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Extending'));
        static::assertTrue($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Implementing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Implementing'));
    }

    public function test_doesnt_get_all_files_recursively(): void
    {
        static::assertEmpty($this->finder->nonRecursive()->get());
        static::assertCount(4, $this->finder->dir('app/Discoverable')->nonRecursive()->get());
    }

    public function test_filters_by_interfaces(): void
    {
        static::assertCount(1, $this->finder->implementing(ArrayAccess::class)->get());
        static::assertCount(2, $this->finder->implementing(Serializable::class)->get());
        static::assertCount(1, $this->finder->implementing(Serializable::class, ArrayAccess::class)->get());
    }

    public function test_filers_by_extend(): void
    {
        static::assertCount(2, $this->finder->extending(ServiceProvider::class)->get());
    }

    public function test_filters_by_method(): void
    {
        static::assertCount(2, $this->finder->methods('register')->get());
        static::assertCount(2, $this->finder->methods('serialize')->get());
        static::assertCount(1, $this->finder->methods('serialize', 'offsetGet')->get());
        static::assertCount(0, $this->finder->methods('something')->get());
    }

    public function test_filters_by_trait(): void
    {
        static::assertCount(2, $this->finder->using('App\Discoverable\Traits')->get());
        static::assertCount(1, $this->finder->using('App\Discoverable\Traits', 'App\Discoverable\Subdirectory\Traits')->get());
        static::assertCount(0, $this->finder->using('InvalidTrait')->get());
    }

    public function test_filters_by_property(): void
    {
        static::assertCount(2, $this->finder->properties('foo')->get());
        static::assertCount(0, $this->finder->properties('bar')->get());
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $filesystem = new Filesystem();

        $filesystem->cleanDirectory(static::$appPath);
    }
}
