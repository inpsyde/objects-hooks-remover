<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

/**
 * phpcs:disable Inpsyde.CodeQuality.StaticClosure.PossiblyStaticClosure
 */
class MatchClosureTest extends TestCase
{
    /**
     * @test
     */
    public function testMatchClosureWithNoConstraints(): void
    {
        $func = function (string $foo): bool {
        };

        static::assertTrue($this->execPrivateFunction('matchClosure', $func));
        static::assertTrue($this->execPrivateFunction('matchClosure', $func, null, null));
    }

    /**
     * @test
     */
    public function testMatchClosureWithInvalidTargetThis(): void
    {
        $func = function (string $foo): bool {
        };

        static::assertFalse($this->execPrivateFunction('matchClosure', $func, true));
        static::assertFalse($this->execPrivateFunction('matchClosure', $func, ''));
        static::assertFalse($this->execPrivateFunction('matchClosure', $func, 'Meh'));
    }

    /**
     * @test
     */
    public function testMatchClosureWithFalseTargetThisMatchesStatic(): void
    {
        $func1 = function (string $foo): bool {
        };
        $func2 = static function (string $foo): bool {
        };

        static::assertFalse($this->execPrivateFunction('matchClosure', $func1, false));
        static::assertTrue($this->execPrivateFunction('matchClosure', $func2, false));
    }

    /**
     * @test
     */
    public function testMatchClosureWithObjectTargetThisMatchesObject(): void
    {
        $func = function (string $foo): bool {
        };

        static::assertTrue($this->execPrivateFunction('matchClosure', $func, $this));
        static::assertFalse($this->execPrivateFunction('matchClosure', $func, clone $this));
    }

    /**
     * @test
     */
    public function testMatchClosureWithClassTargetThisMatchesObject(): void
    {
        $func = function (string $foo): bool {
        };

        static::assertTrue($this->execPrivateFunction('matchClosure', $func, __CLASS__));
        static::assertTrue($this->execPrivateFunction('matchClosure', $func, parent::class));
    }

    /**
     * @test
     */
    public function testMatchClosureWithNoTargetArgsMatchesNoParams(): void
    {
        $func1 = function (): bool {
        };
        $func2 = function (string $foo): bool {
        };

        static::assertTrue($this->execPrivateFunction('matchClosure', $func1, null, []));
        static::assertFalse($this->execPrivateFunction('matchClosure', $func2, null, []));
        static::assertFalse($this->execPrivateFunction('matchClosure', $func1, null, ['$foo']));
        static::assertTrue($this->execPrivateFunction('matchClosure', $func2, null, ['$foo']));
    }

    /**
     * @test
     * @dataProvider provideMatchClosure
     *
     * @param bool $expected
     * @param mixed $targetThis
     * @param array $targetArgs
     * @return void
     */
    public function testMatchClosure(bool $expected, $targetThis, array $targetArgs): void
    {
        if ($targetThis === '__THIS__') {
            $targetThis = $this;
        }

        $func = function (?string $foo, int $bar): bool {
        };

        static::assertSame(
            $expected,
            $this->execPrivateFunction('matchClosure', $func, $targetThis, $targetArgs)
        );
    }

    /**
     * @return \Generator
     */
    public static function provideMatchClosure(): \Generator
    {
        yield from [
            [true, null, ['$foo', '$bar']],
            [true, null, ['$foo' => '?string', '$bar' => 'int']],
            [true, '__THIS__', ['$foo', '$bar']],
            [true, '__THIS__', ['$foo' => '?string', '$bar' => 'int']],
            [true, __CLASS__, ['$foo', '$bar']],
            [true, __CLASS__, ['$foo' => '?string', '$bar' => 'int']],
            [false, '', ['$foo', '$bar']],
            [false, '', ['$foo' => '?string', '$bar' => 'int']],
            [false, null, ['$foo', '$bar', '$z']],
            [false, null, ['$foo' => 'string', '$bar' => 'int']],
            [false, '__THIS__', ['$foo']],
            [false, '__THIS__', ['$foo' => '?string', '$bar' => '?int']],
            [false, __CLASS__, ['$foo', 'y']],
            [false, __CLASS__, ['$foo' => '?string', '$bar']],
        ];
    }
}
