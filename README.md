# Object Hooks Remover

> Package to remove WordPress hook callbacks that uses object methods or closures.

----

## Minimum Requirements and Dependencies

_Object Hooks Remover_ is a [Composer](https://getcomposer.org) package, installable via the package name `inpsyde/object-hooks-remover`.

It has no userland dependencies, it just requires **PHP 7+**.

When installed for development, via Composer, _Object Hooks Remover_ also requires:

- `phpunit/phpunit` (BSD-3-Clause)

----

## Intro (or "What is this?")

WordPress [plugin API](https://developer.wordpress.org/plugins/hooks/) has a partly incomplete implementation.

[`add_action`](https://developer.wordpress.org/reference/functions/add_action/) and [`add_filter`](https://developer.wordpress.org/reference/functions/add_filter/)
accepts as "callback" any kind  PHP callable:

- named functions
- static object methods
- dynamic object methods
- anonymous functions
- invokable objects

the functions to remove hooks, [`remove_action`](https://developer.wordpress.org/reference/functions/remove_action/) and [`remove_filter`](https://developer.wordpress.org/reference/functions/remove_filter/),
only works with named functions and static object methods (2 of the 5 types of callbacks).

Well, ok, this is not completely true. `remove_action` and `remove_filter` can also be used to remove hooks with object 
methods or closures when the _exact instance_ used to add the hook is available, but many and many times that's not the case.

This package provides 5 functions that can be used to remove hooks which uses object methods or closures even without having 
access to the instances of the objects used.

The package functions are:

- `Inpsyde\remove_object_hook`
- `Inpsyde\remove_closure_hook`
- `Inpsyde\remove_class_hook`
- `Inpsyde\remove_instance_hook`
- `Inpsyde\remove_invokable_hook`

You might notice that there's no difference between action and filters because, expecially in removing, there's absolutely
no difference between the two. In fact, this is the code that WordPress core for `remove_action`:

```php
function remove_action( $tag, $function_to_remove, $priority = 10 ) {
	return remove_filter( $tag, $function_to_remove, $priority );
}
```

----


## `Inpsyde\remove_object_hook`

The signature of this function is the following:

```php
function remove_object_hook(
	string $hook,
	string $class_name,
	string $method_name = null,
	int $priority = null,
	bool $remove_static_callbacks = FALSE
): int
```

This function is used to remove hook callbacks that use object methods. By default only targets dynamic methods, but can 
be used for static methods as well.

The first mandatory param is the "tag" we want to remove the callback from.

The second mandatory param is the object class name.

The third optional param is the method name. If not provided (or `null`), it means "all the methods".

The fourth optional param is priority. Unlike WordPress `remove_action`/`remove_filter` not providing a priority means
"all the priorities".

The fifth optional param is a boolean that defaults to false. When true the function will remove both static and dynamic
methods.

The return value of this and all the other function of the package is the number of callbacks removed.

**Example**:

```php
// Somewhere...
class Foo {

	public function __construct() {
    	add_action( 'init', [ $this, 'init' ], 99 );
    }

	public function init() {
		// some code here...
	}
}

nee Foo();

// Somewhere **else**...
remove_object_hook( 'init', Foo::class, 'init' );
```

----


## `Inpsyde\remove_closure_hook`

This function targets hook callbacks added using anonymous functions (aka closures).

Closures are the most tricky callbacks to remove, because it is hard to distinguish them.

In facts, all the closures are in PHP instances of the same class, `Closure`, and not having a method name there's very 
little left to distinguish one closure from another.

This function uses two ways to distinguish closures:

- the object bound to the closure
- the closure signature.

### About closures bound object

Closures can be bound to an object, that means that inside the closure block `$this` refers to the bound object.

When a closure is created inside a class dynamic method, the object binding is automatic: inside the closure, `$this`
refers to the instance where the closure is created.

When a closure is created inside a static method or outside of any class, inside the closure, `$this` is not defined at 
all (i.e. the closure is not bound).

It worth nothing that:

- Closure can be "bound" to any object after they are crated (see docs for [`Closure::bind`](http://php.net/manual/en/closure.bind.php) 
  and [`Closure::bindTo`](http://php.net/manual/en/closure.bindto.php)).
  So even closures created outside classes or inside static methods might have a bound object.
- [Closures can be created as "static"](http://php.net/manual/en/functions.anonymous.php#functions.anonymous-functions.static).
  Static closures are never bound so don't have access to any `$this`, and can't be bound to any object after creation.
  Any attempt to bind a static closure will fail and result in a warning.
  

### Function Signature

`Inpsyde\remove_closure_hook` signature is:

```php
function remove_closure_hook(
	string $hook,
	$target_this = null,
	array $target_args = null,
	int $priority = null
): int 
```

The **second optional param**, `$target_this`, can be used to identify the `$this` of the closure that need to be removed.

It can be:

- `null`, which means "all of them", i.e. the function will not take into account the object bound to closure to see
  if the closure should be removed.
- `false`, the function will only remove static closures or closure with no bound object.
- a string containing a class name, the function will only remove closures having a bound object of the given class.
- an object instance, the function will only remove closures bound to given object.

The **third optional param**, `$target_args` is an array that can be used to distinguish closures by they arguments.

For example, a closure like this:

```php
$closure = function (string $foo, int $bar, $baz ) { /*... */ };
```

can be targeted just by param _names_, passing an array like:

```php
[ '$foo', '$bar', '$baz' ]
```

or by _names_ and _types_, passing an array like:


```php
[ '$foo' => string, '$bar' => int, '$baz' => null ]
```

The two styles can't be mixed, if the type declaration is used for one param must be used for all of them.
In case any of the param has no type declaration, `null` has to be used as shown above.
When the param type is an object, the fully qualified name must be used.

It is also possible to pass `null` as third argument (or don't pass anything, which is the same because the param defaults to `null`)
and in that case closures to be removed will be only distinguished by the bound `$this`.

In the case both second and third argument are `null`, which is the default, all closures added to given hook are removed 
(only optionally filtered by priority).

By the means of bound `$this`, signature, and priority, it is possible to *very effectively* distinguish closures to remove,
the only possibility that two closures can't be distinguished one from the other is that they both are added to the same hook,
at the same priority, from the same class and they have the same signature...

### Usage example

```php
// Somewhere in a plugin...
class Foo {

	public __construct() {
    	add_filter( 'the_title', function( $title ) { /* ... */ } );
    	add_filter( 'the_content', function( string $content ) { /* ... */ } );
    }

}


// Somewhere *else*...
remove_closure_hook( 'the_title', Foo::class, [ '$title' ] );
remove_closure_hook( 'the_content', Foo::class, [ '$content' => 'string' ], 10 );
```

----


## `Inpsyde\remove_class_hook`

Similar to `remove_object_hook` this function targets *only* static methods.

The signature is:

```php
function remove_class_hook(
	string $hook,
	string $class_name,
	string $method_name = null,
	int $priority = null
): int
```

Example:

```php
// Somewhere...
class Foo {

	public static function instance() {
    	add_action( 'init', [ __CLASS__, 'init' ], 99 );
    }

	public static function init() {
		// some code here...
	}
}


// Somewhere **else**...
remove_class_hook( 'init', Foo::class, 'init' );
```

Even if static class methods could be removed via `remove_action` / `remove_filter`, this function can be still
useful because can remove callbacks from any priority and even without specifying a method name. For example:

```php
remove_class_hook( 'init', Foo::class );
```

can be used to remove all the static methods of `Foo` class that are added to `init` hook.

----


## `Inpsyde\remove_instance_hook`

This function can be used to remove hook callbacks added with a specific object instance.

When holding the exact instance used to add some hooks, it would be possible to remove those hooks via core 
functions `remove_action` / `remove_filter`, but this function can be still useful because in a single call remove all 
the callbacks that uses the instance, no matter the method or the priority used (even if priority can be optionally took 
into account).

`remove_instance_hook` signature is:

```php
remove_instance_hook( 
	string $hook,
	$object_instance,
	int $priority = null
) : int;
```

**Example**:

```php
// Somewhere...
class Foo {

	public __construct() {
    	add_filter( 'the_title', [ $this, 'the_title_early', 1 ] );
    	add_filter( 'the_title', [ $this, 'the_title_late', 9999 ] );
    	add_filter( 'the_content', [ $this, 'the_content' ] );
    }

}

global $foo;
$foo = new Foo();


// Somewhere **else**...
global $foo;
remove_instance_hook( 'the_title', $foo ); // remove 2 callbacks
remove_instance_hook( 'the_content', $foo );
```

----


## `Inpsyde\remove_invokable_hook`

This function targets hooks that were added with [invokable objects](http://php.net/manual/en/language.oop5.magic.php#object.invoke).

The signature:

```php
function remove_invokable_hook(
	string $hook,
	string $class_name,
	int $priority = null
) : int;
```

**Example**:

```php
// Somewhere...
class Foo {

	public __construct() {
    	add_filter( 'template_redirect', $this );
    }
    
    public __invoke() {
        /* some code here */
    }

}


// Somewhere **else**...
remove_invokable_hook( 'template_redirect', Foo::class );
```

Note that this function is no more than a shortcut for using `Inpsyde\remove_object_hook` passing `__invoke` as method 
name param.

----


## License and Copyright

Copyright (c) 2017 Inpsyde GmbH.

_Object Hooks Remover_ code is licensed under [MIT license](https://opensource.org/licenses/MIT).

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.