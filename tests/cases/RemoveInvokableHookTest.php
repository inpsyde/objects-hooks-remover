<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use function Inpsyde\remove_invokable_hook;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveInvokableHookTest extends TestCase
{
    /**
     * @return void
     */
    public function testRemoveInvokableHookWithoutPriority(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', $this, 11);
                    add_action('init', $this, 22);
                    add_action('init', $this, 33);
                }
                
                public function __invoke()
                {
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_invokable_hook('init', \Foo::class, 10));
        static::assertSame(3, remove_invokable_hook('init', \Foo::class));
    }

    /**
     * @return void
     */
    public function testRemoveInvokableHookWithPriority(): void
    {
        eval(
            <<<'PHP'
            class Foo
            {
                public function __construct()
                {
                    add_action('init', $this, 11);
                    add_action('init', $this, 22);
                    add_action('init', $this, 33);
                }
                
                public function __invoke()
                {
                }
            }
            PHP
        );

        new \Foo();

        static::assertSame(0, remove_invokable_hook('init', \Foo::class, 10));
        static::assertSame(1, remove_invokable_hook('init', \Foo::class, 11));
        static::assertSame(1, remove_invokable_hook('init', \Foo::class, 22));
        static::assertSame(1, remove_invokable_hook('init', \Foo::class, 33));
    }
}
