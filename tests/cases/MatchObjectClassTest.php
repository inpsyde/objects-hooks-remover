<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use PHPUnit\Framework\Assert;

class MatchObjectClassTest extends TestCase
{
    /**
     * @test
     */
    public function testMatchObjectClassFailsForInvalidTarget(): void
    {
        static::assertFalse($this->execPrivateFunction('matchObjectClass', 'Foo', 'Foo'));
    }

    /**
     * @test
     */
    public function testMatchObjectClassAnonymous(): void
    {
        $obj = new class () {
        };

        static::assertTrue($this->execPrivateFunction('matchObjectClass', $obj, 'object'));
        static::assertTrue($this->execPrivateFunction('matchObjectClass', $obj, '@anonymous'));
        static::assertFalse($this->execPrivateFunction('matchObjectClass', $obj, 'object', true));
    }

    /**
     * @test
     */
    public function testMatchObjectClassStdClass(): void
    {
        $obj = (object) ['foo'];

        static::assertTrue($this->execPrivateFunction('matchObjectClass', $obj, 'object', false));
        static::assertFalse($this->execPrivateFunction('matchObjectClass', $obj, '@anonymous'));
        static::assertTrue($this->execPrivateFunction('matchObjectClass', $obj, 'object', true));
    }

    /**
     * @test
     * @dataProvider provideMatchObject
     *
     * @param bool $expected
     * @param mixed $targetObject
     * @param string $targetClass
     * @param bool $exact
     * @return void
     */
    public function testMatchObject(
        bool $expected,
        $targetObject,
        string $targetClass,
        bool $exact
    ): void {

        if ($targetObject === '__THIS__') {
            $targetObject = $this;
        }

        static::assertSame(
            $expected,
            $this->execPrivateFunction('matchObjectClass', $targetObject, $targetClass, $exact)
        );
    }

    /**
     * @return \Generator
     */
    public static function provideMatchObject(): \Generator
    {
        yield from [
            [true, '__THIS__', 'object', false],
            [true, '__THIS__', __CLASS__, false],
            [true, '__THIS__', parent::class, false],
            [true, '__THIS__', Assert::class, false],
            [true, __CLASS__, 'object', false],
            [true, __CLASS__, __CLASS__, false],
            [true, __CLASS__, parent::class, false],
            [true, __CLASS__, Assert::class, false],
            [false, '__THIS__', 'object', true],
            [true, '__THIS__', __CLASS__, true],
            [false, '__THIS__', parent::class, true],
            [false, '__THIS__', Assert::class, true],
            [false, __CLASS__, 'object', true],
            [true, __CLASS__, __CLASS__, true],
            [false, __CLASS__, parent::class, true],
            [false, __CLASS__, Assert::class, true],
        ];
    }
}
