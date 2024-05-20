<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use function Inpsyde\remove_object_hook;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveObjectHookTest extends TestCase
{
    /**
     * @return void
     */
    public function testRemoveObjectHookDynamicNoPriorityWithMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [$this, 'init'], 99);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_object_hook('init', \Foo::class, 'init', 10));
        static::assertSame(1, remove_object_hook('init', \Foo::class, 'init'));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookDynamicPriorityWithMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [$this, 'init'], 99);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_object_hook('init', \Foo::class, 'init', 999));
        static::assertSame(1, remove_object_hook('init', \Foo::class, 'init', 99));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookDynamicNoPriorityWithoutMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [$this, 'first'], 11);
                    add_action('init', [$this, 'second'], 22);
                    add_action('init', [$this, 'third'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(3, remove_object_hook('init', \Foo::class));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookDynamicPriorityWithoutMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [$this, 'first'], 11);
                    add_action('init', [$this, 'second'], 22);
                    add_action('init', [$this, 'third'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_object_hook('init', \Foo::class, null, 10));
        static::assertSame(1, remove_object_hook('init', \Foo::class, null, 11));
        static::assertSame(1, remove_object_hook('init', \Foo::class, null, 22));
        static::assertSame(1, remove_object_hook('init', \Foo::class, null, 33));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookStaticNoPriorityWithMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [__CLASS__, 'init'], 99);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_object_hook('init', \Foo::class, 'init', 10));
        static::assertSame(0, remove_object_hook('init', \Foo::class, 'init'));
        static::assertSame(1, remove_object_hook('init', \Foo::class, 'init', null, true));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookStaticPriorityWithMethodName(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', [__CLASS__, 'init'], 99);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_object_hook('init', \Foo::class, 'init', 999));
        static::assertSame(0, remove_object_hook('init', \Foo::class, 'init', 99));
        static::assertSame(1, remove_object_hook('init', \Foo::class, 'init', 99, true));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookStaticNoPriorityWithoutMethodName(): void
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

        static::assertSame(0, remove_object_hook('init', \Foo::class));
        static::assertSame(3, remove_object_hook('init', \Foo::class, null, null, true));
    }

    /**
     * @return void
     */
    public function testRemoveObjectHookStaticPriorityWithoutMethodName(): void
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

        static::assertSame(0, remove_object_hook('init', \Foo::class, null, 10));
        static::assertSame(0, remove_object_hook('init', \Foo::class, null, 11));
        static::assertSame(0, remove_object_hook('init', \Foo::class, null, 22));
        static::assertSame(0, remove_object_hook('init', \Foo::class, null, 33));
        static::assertSame(0, remove_object_hook('init', \Foo::class, null, 10, true));

        static::assertSame(11, has_action('init', [\Foo::class, 'first']));
        static::assertSame(22, has_action('init', [\Foo::class, 'second']));
        static::assertSame(33, has_action('init', [\Foo::class, 'third']));

        static::assertSame(1, remove_object_hook('init', \Foo::class, null, 11, true));
        static::assertSame(1, remove_object_hook('init', \Foo::class, null, 22, true));
        static::assertSame(1, remove_object_hook('init', \Foo::class, null, 33, true));

        static::assertFalse(has_action('init', [\Foo::class, 'first']));
        static::assertFalse(has_action('init', [\Foo::class, 'second']));
        static::assertFalse(has_action('init', [\Foo::class, 'third']));
    }
}
