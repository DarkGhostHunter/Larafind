![Agence Olloweb - Unslash (UL) #d9ILr-dbEdg](https://images.unsplash.com/photo-1516382799247-87df95d790b7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80&q=80&w=1280&h=400)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/larafind.svg?style=flat-square)](https://packagist.org/packages/darkghosthunter/larafind) [![License](https://poser.pugx.org/darkghosthunter/larafind/license)](https://packagist.org/packages/darkghosthunter/larafind) ![](https://img.shields.io/packagist/php-v/darkghosthunter/larafind.svg) ![](https://github.com/DarkGhostHunter/Larafind/workflows/PHP%20Composer/badge.svg) [![Coverage Status](https://coveralls.io/repos/github/DarkGhostHunter/Larafind/badge.svg?branch=master)](https://coveralls.io/github/DarkGhostHunter/Larafind?branch=master)

# Larafind

Small utility to find PSR-4 classes from the base project path.

```php
use DarkGhostHunter\Larafind\Facades\Find;
use Illuminate\Database\Eloquent\Scope;

$classes = Find::implementing(Scope::class)->get();
```

You can use this as a way to "auto-discover" classes a developer (or you) may have under a given directory. 

## Requirements

* PHP 8.0
* Laravel 8.x

## Installation

You can install the package via composer:

```bash
composer require darkghosthunter/larafind
```

## Usage

Use the `Find` facade to easy your development pain. The facade creates a "builder" of sorts that will return a list of all discovered PSR-4 compliant classes as a `ReflectionClass`.

The `get()` method will return all the discovered files from the default `app` directory.

```php
use DarkGhostHunter\Larafind\Facades\Find;

$classes = Find::get();
```

### Directory travel

By default, the Finder will use the default `app` directory, but you can use different directories using `dir()`.

```php
use DarkGhostHunter\Larafind\Facades\Find;

$classes = Find::dir('foo/Bar', 'foo/Baz')->get();
```

> Find uses your application root directory as starting point.
 
### Recursive

The discovery is recursive, but you can make it non-recursive using `nonRecursive()`:

```php
use DarkGhostHunter\Larafind\Facades\Find;

$classes = Find::dir('foo/Bar', 'foo/Baz')->nonRecursive()->get();
```

### Filtering

The `Find` returns a Collection, so you can use the [`filter()`](https://laravel.com/docs/collections#method-filter) method to get only those classes that pass a truth test.

To make things simpler, you can use some pre-filtering methods to avoid filtering after you get the collection:

| Method | Description |
|---|---|
| `implementing()`  | Filter by implementing interfaces.
| `extends()`       | Filter by extending class.
| `uses()`          | Filter by used traits (recursive).
| `methods()`       | Filter by public methods.
| `properties()`    | Filter by public properties.

```php
use DarkGhostHunter\Larafind\Facades\Find;
use Illuminate\Database\Eloquent\Model;

$arrayAccessible = Find::implementing(ArrayAccess::class)->get();

$eloquentModels = Find::extending(Model::class)->get();

$usesTraits = Find::using('App\MyCustomTrait')->get();

$hasMethod = Find::methods('handle', 'terminate')->get();

$hasProperties = Find::properties('service', 'model')->get();
```

> Files that don't respect PSR-4 name conventions are discarded.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
