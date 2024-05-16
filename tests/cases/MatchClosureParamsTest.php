<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

class MatchClosureParamsTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideMatchClosureTypedParams
     */
    public function testMatchClosureTypedParams(array $input, bool $expected): void
    {
        $func = static function (?string $foo, int $bar): void {
        };
        $params = (new \ReflectionFunction($func))->getParameters();

        static::assertSame(
            $expected,
            $this->execPrivateFunction('matchClosureParams', $params, $input)
        );
    }

    /**
     * @return \Generator
     */
    public function provideMatchClosureTypedParams(): \Generator
    {
        yield from [
            [['$foo', '$bar'], true],
            [['$foo'], false],
            [['$bar'], false],
            [['$foo', '$bar', '$z'], false],
            [['$foo', 'y'], false],
            [['x', '$bar'], false],
            [['string', 'int'], false],
            [['string', 'int'], false],
            [['$foo' => '?string', '$bar' => 'int'], true],
            [['$foo' => 'string', '$bar' => 'int'], false],
            [['$foo' => '?string', '$bar' => '?int'], false],
            [['$foo' => '?string', '$bar'], false],
        ];
    }

    /**
     * @test
     * @dataProvider provideMatchClosureUntypedParams
     */
    public function testMatchClosureUntypedParams(array $input, bool $expected): void
    {
        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        $func = static function ($foo, $bar): void {
            // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        };
        $params = (new \ReflectionFunction($func))->getParameters();

        static::assertSame(
            $expected,
            $this->execPrivateFunction('matchClosureParams', $params, $input)
        );
    }

    /**
     * @return \Generator
     */
    public function provideMatchClosureUntypedParams(): \Generator
    {
        yield from [
            [['$foo', '$bar'], true],
            [['$foo'], false],
            [['$bar'], false],
            [['$foo', '$bar', '$z'], false],
            [['$foo', 'y'], false],
            [['x', '$bar'], false],
            [['string', 'int'], false],
            [['string', 'int'], false],
            [['$foo' => null, '$bar' => null], true],
            [['$foo' => 'mixed', '$bar' => null], true],
            [['$foo' => 'mixed', '$bar' => 'mixed'], true],
            [['$foo' => null, '$bar' => 'mixed'], true],
            [['$foo' => 'mixed', '$bar'], false],
            [['$foo', '$bar' => 'mixed'], false],
        ];
    }

    /**
     * @test
     * @dataProvider provideMatchClosurePartiallyTypedParams
     */
    public function testMatchClosurePartiallyTypedParams(array $input, bool $expected): void
    {
        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        $func = static function ($foo, ?int $bar): void {
            // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        };
        $params = (new \ReflectionFunction($func))->getParameters();

        static::assertSame(
            $expected,
            $this->execPrivateFunction('matchClosureParams', $params, $input)
        );
    }

    /**
     * @return \Generator
     */
    public function provideMatchClosurePartiallyTypedParams(): \Generator
    {
        yield from [
            [['$foo', '$bar'], true],
            [['$foo'], false],
            [['$bar'], false],
            [['$foo', '$bar', '$z'], false],
            [['', '?int'], false],
            [['mixed', '?int'], false],
            [['null', '?int'], false],
            [['$foo' => null, '$bar' => '?int'], true],
            [['$foo' => null, '$bar' => 'int'], false],
            [['$foo', '$bar' => '?int'], false],
            [['$foo' => 'mixed', '$bar' => '?int'], true],
            [['$foo' => 'string', '$bar' => '?int'], false],
            [['$foo' => '', '$bar' => '?int'], false],
        ];
    }
}
