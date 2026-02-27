<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

class ParseCallbackDataTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideParseCallbackData
     *
     * @param mixed $input
     * @param array $expected
     * @return void
     */
    public function testParseCallbackData($input, array $expected): void
    {
        static::assertSame(
            $expected,
            $this->execPrivateFunction('parseCallbackData', $input)
        );
    }

    /**
     * @return \Generator
     */
    public static function provideParseCallbackData(): \Generator
    {
        $class = __CLASS__;
        $method = __METHOD__;
        $function = __FUNCTION__;

        $invokable = new class ()
        {
            public function __invoke()
            {
            }
        };
        $invokableClass = get_class($invokable);
        $invokableId = _wp_filter_build_unique_id('', $invokable, random_int(1, 10000));

        $closure = static function (): void {
        };
        $closureId = _wp_filter_build_unique_id('', $closure, random_int(1, 10000));

        $object = new class ()
        {
            public function example(): void
            {
            }
        };
        $objectId = _wp_filter_build_unique_id('', [$object, 'example'], random_int(1, 10000));
        $objectClass = get_class($object);

        yield from [
            ['', ['', null, '', '']],
            [[], ['', null, '', '']],
            [['foo' => 'function'], ['', null, '', '']],
            [['function' => []], ['', null, '', '']],
            [['function' => [$class]], ['', null, '', '']],
            [['function' => [$class, $function, 'meh']], ['', null, '', '']],
            [['function' => 'foo'], ['', null, '', '']],
            [['function' => '::'], ['', null, '', '']],
            [['function' => '::' . $function], ['', null, '', '']],
            [['function' => $class . '::'], ['', null, '', '']],
            [['function' => $method], [$method, null, $class, __FUNCTION__]],
            [['function' => $invokable], [$invokableId, $invokable, $invokableClass, '__invoke']],
            [['function' => $closure], [$closureId, $closure, \Closure::class, '__invoke']],
            [['function' => [$class, $function]], [$method, null, $class, $function]],
            [['function' => [$object, 'example']], [$objectId, $object, $objectClass, 'example']],
        ];
    }
}
