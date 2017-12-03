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

use function Inpsyde\ObjectHooksRemover\parse_callback_data;

class ParseCallbackDataTest extends TestCase {

	public function test_only_valid_object_callback_are_taken_into_account() {

		$closure = function () {
		};

		$empty_array = parse_callback_data( [], 10 );
		$empty_func  = parse_callback_data( [ 'function' => '' ], 10 );
		$string_func = parse_callback_data( [ 'function' => 'strtolower' ], 10 );
		$array_func  = parse_callback_data( [ 'function' => [ __CLASS__, __METHOD__ ] ], 10 );
		$object_func = parse_callback_data( [ 'function' => $closure ], 10 );

		static::assertCount( 0, $empty_array );
		static::assertCount( 0, $empty_func );
		static::assertCount( 0, $string_func );
		static::assertCount( 5, $array_func );
		static::assertCount( 5, $object_func );
	}

	public function test_closure_data() {

		$closure = function () {
		};

		list( $idx, $priority, $object, $class, $method ) = parse_callback_data( [ 'function' => $closure ], 222 );

		static::assertSame( spl_object_hash( $closure ), $idx );
		static::assertSame( 222, $priority );
		static::assertSame( $closure, $object );
		static::assertSame( \Closure::class, $class );
		static::assertSame( '__invoke', $method );

	}

	public function test_dyn_method_data() {

		$data = parse_callback_data( [ 'function' => [ $this, __METHOD__ ] ], - 1 );
		list( $idx, $priority, $object, $class, $method ) = $data;

		static::assertSame( spl_object_hash( $this ) . __METHOD__, $idx );
		static::assertSame( - 1, $priority );
		static::assertSame( $this, $object );
		static::assertSame( __CLASS__, $class );
		static::assertSame( __METHOD__, $method );
	}

	public function test_static_method_data() {

		$data = parse_callback_data( [ 'function' => [ \SplFixedArray::class, 'fromArray' ] ], 123 );
		list( $idx, $priority, $object, $class, $method ) = $data;

		static::assertSame( \SplFixedArray::class . '::fromArray', $idx );
		static::assertSame( 123, $priority );
		static::assertSame( null, $object );
		static::assertSame( \SplFixedArray::class, $class );
		static::assertSame( 'fromArray', $method );
	}
}