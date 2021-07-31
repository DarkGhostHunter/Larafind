<?php

namespace Tests;

use ArrayAccess;
use Composer\Autoload\ClassLoader;
use DarkGhostHunter\Larafind\Finder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
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
            Arr::first(ClassLoader::getRegisteredLoaders())->addPsr4('App\\', $this->app->path());
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

    public function test_finds_all_files_in_app(): void
    {
        $files = $this->finder->path('Discoverable')->get();

        static::assertCount(8, $files);
        static::assertTrue($files->has('App\Discoverable\ClassUsing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\ClassUsing'));
        static::assertTrue($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Extending'));
        static::assertTrue($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Implementing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Implementing'));
    }

    public function test_finds_all_files_in_app_not_recursive(): void
    {
        $files = $this->finder->path('Discoverable')->nonRecursive()->get();

        static::assertCount(4, $files);
        static::assertTrue($files->has('App\Discoverable\ClassUsing'));
        static::assertTrue($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Implementing'));
    }

    public function test_finds_all_files_in_project_directory(): void
    {
        $files = $this->finder->basePath('app/Discoverable')->get();

        static::assertCount(8, $files);
        static::assertTrue($files->has('App\Discoverable\ClassUsing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\ClassUsing'));
        static::assertTrue($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Extending'));
        static::assertTrue($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Implementing'));
        static::assertTrue($files->has('App\Discoverable\Subdirectory\Implementing'));
    }

    public function test_finds_all_files_in_project_directory_non_recursive(): void
    {
        $files = $this->finder->basePath('app/Discoverable')->nonRecursive()->get();

        static::assertCount(4, $files);
        static::assertTrue($files->has('App\Discoverable\ClassUsing'));
        static::assertTrue($files->has('App\Discoverable\Extending'));
        static::assertTrue($files->has('App\Discoverable\NormalClass'));
        static::assertTrue($files->has('App\Discoverable\Implementing'));
    }

    public function test_exception_when_looking_into_not_autoload_dir(): void
    {
        $this->expectExceptionMessage('The files in [storage] are not registered for class autoloading.');
        $this->expectException(RuntimeException::class);

        $this->finder->basePath('storage')->get();
    }

    public function test_filters_by_interfaces(): void
    {
        static::assertCount(1, $this->finder->implementing(ArrayAccess::class)->get());
        static::assertCount(2, $this->finder->implementing(Serializable::class)->get());
        static::assertCount(1, $this->finder->implementing(Serializable::class, ArrayAccess::class)->get());
    }

    public function test_filters_by_extend(): void
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
