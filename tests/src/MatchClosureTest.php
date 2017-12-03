<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the objects-hooks-remover package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Tests;

use PHPUnit\Framework\TestCase;
use function Inpsyde\ObjectHooksRemover\match_closure;

class MatchClosureTest extends TestCase {

	public function test_always_match_if_no_restrictions() {

		$cb = function () {

		};

		static::assertTrue( match_closure( $cb ) );
	}

	public function test_target_static() {

		$cb = static function () {

		};

		static::assertTrue( match_closure( $cb, null ) );
		static::assertFalse( match_closure( $cb, $this ) );
		static::assertTrue( match_closure( $cb, FALSE ) );
	}

	public function test_match_specific_target() {

		$cb = function () {

		};

		static::assertTrue( match_closure( $cb, $this ) );
	}

	public function test_not_match_if_wrong_number_args_num() {

		$cb = function ( $foo, $bar, $baz ) {

		};

		static::assertFalse( match_closure( $cb, $this, [ 'foo', 'bar' ] ) );
	}

	public function test_pass_empty_array_of_args_is_not_the_same_of_pass_null() {

		$cb = function ( $foo ) {

		};

		static::assertFalse( match_closure( $cb, $this, [] ) );
		static::assertTrue( match_closure( $cb, $this, null ) );
	}

	public function test_matching_args_by_name_only() {

		$cb = function ( $foo, $bar, $baz ) {

		};

		$bound = \Closure::bind( $cb, new \stdClass() );

		static::assertTrue( match_closure( $cb, null, [ '$foo', '$bar', '$baz' ] ) );
		static::assertTrue( match_closure( $cb, $this, [ '$foo', '$bar', '$baz' ] ) );
		static::assertTrue( match_closure( $bound, null, [ '$foo', '$bar', '$baz' ] ) );
		static::assertFalse( match_closure( $bound, $this, [ '$foo', '$bar', '$baz' ] ) );
	}

	public function test_matching_args_by_name_and_type() {

		$cb = function ( $foo, array $bar, \ArrayObject $baz ) {

		};

		$right_args_1 = [
			'$foo' => null,
			'$bar' => 'array',
			'$baz' => \ArrayObject::class,
		];

		$right_args_2 = [
			'$foo' => 'null',
			'$bar' => 'array',
			'$baz' => \ArrayObject::class,
		];

		$wrong_args_1 = [
			'$foo' => null,
			'$bar' => 'array',
			'$baz' => null,
		];

		$wrong_args_2 = [
			'$foo' => null,
			'$bar' => 'array',
		];

		$wrong_args_3 = [
			'$foo',
			'$bar',
			'$baz' => \ArrayObject::class,
		];

		$wrong_args_4 = [
			'foo' => '',
			'bar' => 'array',
			'baz' => \ArrayObject::class,
		];

		$wrong_args_5 = [
			'$foo' => null,
			'$bar' => 'array',
			'baz' => \ArrayObject::class,
		];

		static::assertTrue( match_closure( $cb, null, $right_args_1 ) );
		static::assertTrue( match_closure( $cb, $this, $right_args_2 ) );
		static::assertFalse( match_closure( $cb, null, $wrong_args_1 ) );
		static::assertFalse( match_closure( $cb, null, $wrong_args_2 ) );
		static::assertFalse( match_closure( $cb, null, $wrong_args_3 ) );
		static::assertFalse( match_closure( $cb, null, $wrong_args_4 ) );
		static::assertFalse( match_closure( $cb, null, $wrong_args_5 ) );
	}

	public function test_matching_args_by_type_with_ambiguous_scalars() {

		$cb = function ( int $foo, float $bar, bool $baz ) {

		};

		$right_args_1 = [
			'$foo' => 'int',
			'$bar' => 'float',
			'$baz' => 'bool',
		];

		$right_args_2 = [
			'$foo' => 'integer',
			'$bar' => 'double',
			'$baz' => 'boolean',
		];

		$wrong_args = [
			'$foo' => 'number',
			'$bar' => 'double',
			'$baz' => 'boolean',
		];

		static::assertTrue( match_closure( $cb, null, $right_args_1 ) );
		static::assertTrue( match_closure( $cb, null, $right_args_2 ) );
		static::assertFalse( match_closure( $cb, null, $wrong_args ) );
	}
}