<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

class NormalizeTargetArgsListTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideNormalizeTargetArgsList
     */
    public function testNormalizeTargetArgsList(array $input, array $expected): void
    {
        $actual = $this->execPrivateFunction('normalizeTargetArgsList', $input);

        static::assertSame($expected, $actual);
    }

    /**
     * @return \Generator
     */
    public static function provideNormalizeTargetArgsList(): \Generator
    {
        yield from [
            [
                [],
                [true, []],
            ],
            [
                ['$foo', '$bar', '$baz'],
                [true, ['$foo' => 'mixed', '$bar' => 'mixed', '$baz' => 'mixed']],
            ],
            [
                ['$foo' => 'string', '$bar' => 'string|null', '$baz' => 'int'],
                [false, ['$foo' => 'string', '$bar' => 'string|null', '$baz' => 'int']],
            ],
            [
                ['$foo', 'bar', '$baz'],
                [false, []],
            ],
            [
                ['$foo' => 'string', 'string|null', '$baz' => 'int'],
                [false, []],
            ],
            [
                ['$foo', '$bar' => 'null', '$baz'],
                [false, []],
            ],
            [
                ['foo'],
                [false, []],
            ],
        ];
    }
}
