<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use function Inpsyde\remove_static_method_hook;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveStaticMethodHookTest extends TestCase
{
    /**
     * @return void
     */
    public function testWithoutPriorityWithoutMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [__CLASS__, 'first'], 11);
                    add_action('init', [__CLASS__, 'second'], 22);
                    add_action('init', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_static_method_hook('init', \Foo::class, null, 10));
        static::assertSame(3, remove_static_method_hook('init', \Foo::class));
    }

    /**
     * @return void
     */
    public function testWithPriorityWithoutMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [__CLASS__, 'first'], 11);
                    add_action('init', [__CLASS__, 'second'], 22);
                    add_action('init', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_static_method_hook('init', \Foo::class, null, 10));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, null, 11));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, null, 22));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, null, 33));
    }

    /**
     * @return void
     */
    public function testWithoutPriorityWithMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [__CLASS__, 'foo'], 11);
                    add_action('init', [__CLASS__, 'foo'], 12);
                    add_action('init', [__CLASS__, 'bar'], 22);
                    add_action('init', [__CLASS__, 'baz'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_static_method_hook('init', \Foo::class, 'init'));
        static::assertSame(2, remove_static_method_hook('init', \Foo::class, 'foo'));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, 'bar'));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, 'baz'));
    }

    /**
     * @return void
     */
    public function testWithPriorityWithMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [__CLASS__, 'foo'], 11);
                    add_action('init', [__CLASS__, 'foo'], 12);
                    add_action('init', [__CLASS__, 'bar'], 22);
                    add_action('init', [__CLASS__, 'baz'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_static_method_hook('init', \Foo::class, 'foo', 10));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, 'foo', 11));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, 'foo', 12));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, 'bar', 22));
        static::assertSame(1, remove_static_method_hook('init', \Foo::class, 'baz', 33));
        static::assertSame(0, remove_static_method_hook('init', \Foo::class, 'baz', 11));
    }
}
