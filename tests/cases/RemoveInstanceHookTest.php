<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use function Inpsyde\remove_instance_hook;

/**
 * @runTestsInSeparateProcesses
 */
class RemoveInstanceHookTest extends TestCase
{
    /**
     * @return void
     */
    public function testRemoveInstanceHookNoPriority(): void
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

        $foo = new \Foo();

        static::assertSame(0, remove_instance_hook('init', $this));
        static::assertSame(3, remove_instance_hook('init', $foo));
    }

    /**
     * @return void
     */
    public function testRemoveInstanceHookPriority(): void
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

        $foo = new \Foo();

        static::assertSame(0, remove_instance_hook('init', $foo, 999));
        static::assertSame(1, remove_instance_hook('init', $foo, 11));
        static::assertSame(1, remove_instance_hook('init', $foo, 22));
        static::assertSame(1, remove_instance_hook('init', $foo, 33));
    }
}
