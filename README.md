# Object Hooks Remover

> Package to remove WordPress hook callbacks that use object methods or closures.

[![Static Analysis](https://github.com/inpsyde/objects-hooks-remover/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/inpsyde/objects-hooks-remover/actions/workflows/static-analysis.yml)
[![Unit Tests](https://github.com/inpsyde/objects-hooks-remover/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/inpsyde/objects-hooks-remover/actions/workflows/unit-tests.yml)

---

## What is this?

WordPress [plugin API](https://developer.wordpress.org/plugins/hooks/) has a partly incomplete implementation.

[`add_action`](https://developer.wordpress.org/reference/functions/add_action/) and [`add_filter`](https://developer.wordpress.org/reference/functions/add_filter/) accepts as "callback" any kind PHP callable:

- named functions
- static object methods
- dynamic object methods
- anonymous functions
- invokable objects

The functions to remove hooks, [`remove_action`](https://developer.wordpress.org/reference/functions/remove_action/) and [`remove_filter`](https://developer.wordpress.org/reference/functions/remove_filter/), works without issues only with named functions and static object methods (2 of the 5 types of callbacks).

The remaining cases involving object instances `remove_action` and `remove_filter` can only be used when having access to the object instance that was used to add hooks, but many times that's not available.

This package provides six functions that can be used to remove hooks that use object methods or closures even without having access to the original object instance.

The package functions are:

- `Inpsyde\remove_object_hook()`
- `Inpsyde\remove_closure_hook()`
- `Inpsyde\remove_static_method_hook()`
- `Inpsyde\remove_instance_hook()`
- `Inpsyde\remove_invokable_hook()`
- `Inpsyde\remove_all_object_hooks()`

You might notice there is no difference between action and filters because, especially in removing, there's absolutely no difference between the two.

The return value of all the functions is the number of callbacks removed.

---

## `Inpsyde\remove_object_hook()`

```php
function remove_object_hook(
	string $hook,
	class-string $targetClassName,
	?string $methodName = null,
	?int $targetPriority = null,
	bool $removeStaticCallbacks = false
): int
```

This function is used to remove hook callbacks that use object methods. By default, it only targets dynamic methods, but it can be used for static methods passing `true` to the last parameter.

### Usage Example

```php
// Somewhere...
class Foo
{
	public function __construct()
	{
		add_action('init', [$this, 'init'], 99);
		add_action('template_redirect', [__CLASS__, 'templateRedirect']);
	}
	
	public function init(): void
	{
	}
	
	public static function templateRedirect(): void
	{
	}
}

new Foo();

// Somewhere else...
Inpsyde\remove_object_hook('init', Foo::class, 'init');
Inpsyde\remove_object_hook('init', Foo::class, 'init', removeStaticCallbacks: true);
```


## `Inpsyde\remove_closure_hook()`

This function targets hook callbacks added using anonymous functions (aka closures).

Closures are the most tricky callbacks to remove because it is hard to distinguish them.

In fact, in PHP, all closures are instances of the same class, `Closure`, and not having a method name there's very little left to distinguish one closure from another.

This function uses two ways to distinguish closures:

- the object the closure is bound to
- the closure parameters' name and type

`Inpsyde\remove_closure_hook` signature is:

```php
function remove_closure_hook(
	string $hook,
	?object $targetThis = null,
	?array $targetArgs = null,
	?int $targetPriority = null
): int 
```

The **second optional param**, `$targetThis`, can be used to identify the `$this` of the closure to remove.

It can be:

- `null`, which means "all of them", i.e. the function will not take into account the object bound to closure to see if the closure should be removed or not
- `false`, the function will only remove static closures or closures with no bound object
- a string containing a class name, the function will only remove closures having a bound object of the given class
- an object instance, the function will only remove closures bound to the given object

The **third optional param**, `$targetArgs` is an array that can be used to distinguish closures by their parameters.

For example, a closure like this:

```php
$closure = function (string $foo, int $bar, $baz) { /*... */ };
```

can be targeted just by parameter _names_, passing an array like:

```php
['$foo', '$bar', '$baz']
```

or by parameter _names_ and _types_, passing an array like:


```php
['$foo' => 'string', '$bar' => 'int', '$baz' => null]
```

The two styles can't be mixed, if the type declaration is used for one param it must be used for all of them.
In case any of the parameters have no type declaration, `null` or `"mixed"` must be used.

It is also possible to pass `null` as the third argument (or don't pass anything, which is the same because the param defaults to `null`), and in that case, closures to be removed will be only distinguished by the bound `$this`.

When both the second and the third arguments are `null`, which is the default, all closures added to the given hook are removed (only optionally filtered by priority).

### Usage Example

```php
// Somewhere in a plugin...
class Foo
{
	public function __construct() {
		add_filter('the_title', function($title) { /* ... */ });
		add_filter('the_content', function(string $content) { /* ... */ });
	}
}

new Foo();

// Somewhere else...
Inpsyde\remove_closure_hook('the_title', Foo::class, ['$title']);
Inpsyde\remove_closure_hook('the_content', Foo::class, ['$content' => 'string'], 10);
```


## `Inpsyde\remove_static_method_hook()`

Similarly to `remove_object_hook()` this function targets *only* static methods.

The signature is:

```php
function remove_static_method_hook(
	string $hook,
	class-string $targetClassName,
	?string $targetMethodName = null,
	?int $targetPriority = null
): int
```

### Usage Example

```php
// Somewhere...
class Foo {

	public static function instance()
	{
		add_action('init', [__CLASS__, 'init'], 99);
	}

	public static function init()
	{
	}
}

Foo::instance();

// Somewhere else...
Inpsyde\remove_static_method_hook('init', Foo::class, 'init');
```

Even if static class methods could be removed via `remove_action` / `remove_filter`, this function can be still useful because can remove callbacks from any priority and even without specifying a method name.

For example, we can use the following to remove _all_ the static methods of the `Foo::class` attached to the `init` hook:

```php
remove_static_method_hook('init', Foo::class);
```


## `Inpsyde\remove_instance_hook()`

This function can be used to remove hook callbacks added with a specific object instance.

When having access to the exact instance used to add some hooks, it would be possible to remove those hooks via core functions `remove_action` / `remove_filter`, but this function can still be useful because in a single call can remove all the hooks that use the instance, no matter the method or the priority used.

The `remove_instance_hook` signature is:

```php
remove_instance_hook( 
	string $hook,
	object $targetObject,
	?int $targetPriority = null
): int;
```

### Usage Example

```php
// Somewhere...
class Foo
{
	public function __construct()
	{
		add_filter('the_title', [$this, 'the_title_early', 1]);
		add_filter('the_title', [$this, 'the_title_late', 9999]);
		add_filter('the_content', [$this, 'the_content']);
	}
}

global $foo;
$foo = new Foo();


// Somewhere else...
global $foo;
Inpsyde\remove_instance_hook('the_title', $foo); // remove 2 callbacks
Inpsyde\remove_instance_hook('the_content', $foo);
```


## `Inpsyde\remove_invokable_hook()`

This function targets hooks added with [invokable objects](http://php.net/manual/en/language.oop5.magic.php#object.invoke).

The signature:

```php
function remove_invokable_hook(
	string $hook,
	class-string $targetClassName,
	?int $targetPriority = null
): int;
```

### Usage Example

```php
// Somewhere...
class Foo
{
	public function __construct()
	{
		add_filter('template_redirect', $this);
	}
    
	public function __invoke()
	{
	}
}

new Foo();


// Somewhere else...
Inpsyde\remove_invokable_hook('template_redirect', Foo::class);
```


## `Inpsyde\remove_all_object_hooks()`

```php
function remove_all_object_hooks(
	class-string|object $targetObject,
	?bool $removeStaticCallbacks = null
): int
```

This function is used to remove all hook callbacks that use the given object or class name.

When passing an object instance, it removes all the hook callbacks using that exact instance.

When passing a class name, it removes all the hook callbacks using that class (regardless of the instance).

Static methods are removed when:
- an object instance is passed and `$removeStaticCallbacks` param _is_ `true`
- a class name is passed and `$removeStaticCallbacks` param _is not_ `false`

### Usage Example

```php
// Somewhere...
class Foo
{
	public function __construct()
	{
		add_action('init', [$this, 'init'], 99);
		add_action('template_redirect', [__CLASS__, 'templateRedirect']);
	}
	
	public function init(): void
	{
	}
	
	public static function templateRedirect(): void
	{
	}
}

global $foo;
$foo = new Foo();

// Somewhere else...
global $foo;
Inpsyde\remove_all_object_hooks($foo); // remove "init" hook
Inpsyde\remove_all_object_hooks(Foo::class); // would remove both hooks, but only one left
Inpsyde\remove_all_object_hooks($foo, true); // would remove both hooks, but none left
Inpsyde\remove_all_object_hooks(Foo::class, false); // would remove the "init" hook, but none left
```

---

## Minimum Requirements

_Object Hooks Remover_ is a [Composer](https://getcomposer.org) package, installable via the package name `inpsyde/object-hooks-remover`.

It has no dependencies and requires **PHP 7.4+**.

It is tested and guaranteed to work with WP 5.9+, but _should_ work, at least, with WP 5.3+ (which is the first version officially supporting PHP 7.4).


---


## License

This repository is free software released under the terms of the GNU General Public License version 2 or (at your option) any later version. See [LICENSE](./LICENSE) for the complete license.
