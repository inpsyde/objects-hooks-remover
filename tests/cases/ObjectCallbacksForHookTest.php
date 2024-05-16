<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

/**
 * @runTestsInSeparateProcesses
 */
class ObjectCallbacksForHookTest extends TestCase
{
    /**
     * @test
     */
    public function testEmptyHookReturnsEmptyData(): void
    {
        add_action('foo', __METHOD__, 22);

        static::assertSame([], $this->execPrivateFunction('objectCallbacksForHook', ''));
        static::assertSame([], $this->execPrivateFunction('objectCallbacksForHook', '', 22));
    }

    /**
     * @test
     */
    public function testObjectCallbacksForHook(): void
    {
        $function = static function (): void {
        };
        $fnId = _wp_filter_build_unique_id('', $function, 0);

        add_action('foo', __METHOD__, 11);
        add_action('foo', 'strtolower', 22);
        add_action('bar', $function, 33);
        add_action('bar', ['', ''], 44);
        add_action('bar', [__CLASS__, ''], 55);
        add_action('bar', [__CLASS__, __FUNCTION__], 66);
        add_action('bar', '_wp_filter_build_unique_id', 77);

        static::assertSame(
            [
                [__METHOD__, 11, null, __CLASS__, __FUNCTION__],
            ],
            $this->execPrivateFunction('objectCallbacksForHook', 'foo')
        );

        static::assertSame(
            [
                [__METHOD__, 11, null, __CLASS__, __FUNCTION__],
            ],
            $this->execPrivateFunction('objectCallbacksForHook', 'foo', 11)
        );

        static::assertSame(
            [],
            $this->execPrivateFunction('objectCallbacksForHook', 'foo', 10)
        );

        static::assertSame(
            [
                [$fnId, 33, $function, \Closure::class, '__invoke'],
                [__METHOD__, 66, null, __CLASS__, __FUNCTION__],
            ],
            $this->execPrivateFunction('objectCallbacksForHook', 'bar')
        );

        static::assertSame(
            [
                [$fnId, 33, $function, \Closure::class, '__invoke'],
            ],
            $this->execPrivateFunction('objectCallbacksForHook', 'bar', 33)
        );

        static::assertSame(
            [
                [__METHOD__, 66, null, __CLASS__, __FUNCTION__],
            ],
            $this->execPrivateFunction('objectCallbacksForHook', 'bar', 66)
        );

        static::assertSame(
            [],
            $this->execPrivateFunction('objectCallbacksForHook', 'bar', 10)
        );
    }
}
