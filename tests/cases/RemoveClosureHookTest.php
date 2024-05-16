<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use function Inpsyde\remove_closure_hook;
use function Inpsyde\remove_instance_hook;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveClosureHookTest extends TestCase
{
    /**
     * @return void
     */
    public function testRemoveClosureHookAll(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct() {
                    add_filter('foo', fn ($foo) => true);
                    add_filter('bar', function (string $bar) {});
                    add_filter('foo', static function (string $baz) {});
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(2, remove_closure_hook('foo'));
        static::assertSame(1, remove_closure_hook('bar'));
    }

    /**
     * @return void
     */
    public function testRemoveClosureHookByClass(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct() {
                    add_filter('foo', fn ($foo) => true);
                    add_filter('bar', function (string $bar) {});
                    add_filter('foo', static function (string $baz) {});
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_closure_hook('foo', __CLASS__));
        static::assertSame(1, remove_closure_hook('foo', \Foo::class));
        static::assertSame(1, remove_closure_hook('foo', false));
        static::assertSame(0, remove_closure_hook('bar', false));
        static::assertSame(1, remove_closure_hook('bar', \Foo::class));
    }

    /**
     * @return void
     */
    public function testRemoveClosureHookByParamsUntyped(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct() {
                    add_filter('foo', fn ($foo) => true);
                    add_filter('bar', function (string $bar) {});
                    add_filter('foo', static function (string $baz) {});
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_closure_hook('foo', null, ['$foo' => '']));
        static::assertSame(1, remove_closure_hook('foo', null, ['$foo']));
        static::assertSame(1, remove_closure_hook('foo', null, ['$baz']));
        static::assertSame(1, remove_closure_hook('bar', null, ['$bar']));
    }

    /**
     * @return void
     */
    public function testRemoveClosureHookByParamsTyped(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct() {
                    add_filter('foo', fn ($foo) => true);
                    add_filter('bar', function (string $bar) {});
                    add_filter('foo', static function (string $baz) {});
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(1, remove_closure_hook('foo', null, ['$foo' => null]));
        static::assertSame(1, remove_closure_hook('foo', null, ['$baz' => 'string']));
        static::assertSame(1, remove_closure_hook('bar', null, ['$bar' => 'string']));
    }

    /**
     * @return void
     */
    public function testRemoveClosureHookByClassAndParamsTyped(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct() {
                    add_filter('foo', fn ($foo) => true);
                    add_filter('bar', function (string $bar) {});
                    add_filter('foo', static function (string $baz) {});
                }
            }
            PHP
        );

        $foo = new \Foo();

        static::assertSame(0, remove_closure_hook('foo', \Foo::class, ['$foo' => '']));
        static::assertSame(0, remove_closure_hook('foo', __CLASS__, ['$foo' => null]));
        static::assertSame(0, remove_closure_hook('foo', \Foo::class, ['$foo' => null], 11));
        static::assertSame(1, remove_closure_hook('foo', \Foo::class, ['$foo' => null]));

        static::assertSame(0, remove_closure_hook('foo', false, ['$baz' => 'int']));
        static::assertSame(0, remove_closure_hook('foo', \Foo::class, ['$baz' => 'string']));
        static::assertSame(0, remove_closure_hook('foo', false, ['$baz' => 'string'], 1));
        static::assertSame(1, remove_closure_hook('foo', false, ['$baz' => 'string'], 10));

        static::assertSame(0, remove_closure_hook('bar', \Foo::class, ['$baz' => 'string']));
        static::assertSame(0, remove_closure_hook('bar', __CLASS__, ['$bar' => 'string']));
        static::assertSame(0, remove_closure_hook('bar', \Foo::class, ['$bar' => 'string'], 20));
        static::assertSame(1, remove_closure_hook('bar', $foo, ['$bar' => 'string'], 10));
    }
}
