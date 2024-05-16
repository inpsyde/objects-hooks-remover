<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover\Tests;

use Inpsyde\ObjectHooksRemover\Functions;
use PHPUnit\Framework;

abstract class TestCase extends Framework\TestCase
{
    /**
     * @param string $method
     * @param mixed $args
     * @return mixed
     */
    protected function execPrivateFunction(string $method, ...$args)
    {
        /**
         * @return mixed
         * @bound
         */
        $func = function () use ($method, $args) {
            /** @var callable $callback */
            $callback = [Functions::class, $method];

            return $callback(...$args);
        };

        $func = \Closure::bind($func, null, Functions::class);
        static::assertTrue(is_callable($func));

        return $func();
    }
}
