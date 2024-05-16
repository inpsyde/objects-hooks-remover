<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use function Inpsyde\remove_all_object_hooks;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveAllObjectHooksTest extends TestCase
{
    /**
     * @return void
     */
    public function testByInstance(): void
    {
        eval(
            <<<'PHP'
            class Bar
            {
            }
            class Foo extends Bar
            {
                public function addHooks()
                {
                    add_action('first', [$this, 'first'], 11);
                    add_action('second', [$this, 'second'], 22);
                    add_action('first', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        $foo1 = new \Foo();
        $foo1->addHooks();
        $foo2 = clone $foo1;
        $bar = new \Bar();

        static::assertSame(0, remove_all_object_hooks($foo2));
        static::assertSame(0, remove_all_object_hooks($bar));
        static::assertSame(2, remove_all_object_hooks($foo1));
    }

    /**
     * @return void
     */
    public function testByClass(): void
    {
        eval(
            <<<'PHP'
            class Bar
            {
            }
            class Foo extends Bar
            {
                public function __construct()
                {
                    add_action('first', [$this, 'first'], 11);
                    add_action('second', [$this, 'second'], 22);
                    add_action('first', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_all_object_hooks(\Bar::class));
        static::assertSame(3, remove_all_object_hooks(\Foo::class));
    }

    /**
     * @return void
     */
    public function testByInstanceStatic(): void
    {
        eval(
            <<<'PHP'
            class Bar
            {
            }
            class Foo extends Bar
            {
                public function addHooks()
                {
                    add_action('first', [$this, 'first'], 11);
                    add_action('second', [$this, 'second'], 22);
                    add_action('first', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        $foo1 = new \Foo();
        $foo1->addHooks();
        $foo2 = clone $foo1;
        $bar = new \Bar();

        static::assertSame(0, remove_all_object_hooks($bar, true));
        static::assertSame(1, remove_all_object_hooks($foo2, true));
    }

    /**
     * @return void
     */
    public function testByInstanceAlsoStatic(): void
    {
        eval(
            <<<'PHP'
            class Bar
            {
            }
            class Foo extends Bar
            {
                public function addHooks()
                {
                    add_action('first', [$this, 'first'], 11);
                    add_action('second', [$this, 'second'], 22);
                    add_action('first', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        $foo1 = new \Foo();
        $foo1->addHooks();
        $bar = new \Bar();

        static::assertSame(0, remove_all_object_hooks($bar, true));
        static::assertSame(3, remove_all_object_hooks($foo1, true));
    }

    /**
     * @return void
     */
    public function testByClassNotStatic(): void
    {
        eval(
            <<<'PHP'
            class Bar
            {
            }
            class Foo extends Bar
            {
                public function __construct()
                {
                    add_action('first', [$this, 'first'], 11);
                    add_action('second', [$this, 'second'], 22);
                    add_action('first', [__CLASS__, 'third'], 33);
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_all_object_hooks(\Bar::class, false));
        static::assertSame(2, remove_all_object_hooks(\Foo::class, false));
    }
}
