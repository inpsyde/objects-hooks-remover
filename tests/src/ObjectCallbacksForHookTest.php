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

use function Inpsyde\ObjectHooksRemover\object_callbacks_for_hook;

class ObjectCallbacksForHookTest extends TestCase {

	public function test_object_callbacks_for_hook_one_priority_many_hooks() {

		$parsed = object_callbacks_for_hook( 'init' );
		$parsed->rewind();

		$first  = $parsed->current();
		$parsed->next();
		$second = $parsed->current();
		$parsed->next();
		$third = $parsed->current();

		static::assertCount( 3, $parsed );

		static::assertSame( $this, $first[2] );
		static::assertSame( 'a_public_method', $first[4] );

		static::assertSame( null, $second[2] );
		static::assertSame( static::case_class(), $second[3] );

		static::assertSame( 10, $third[1] );
		static::assertInstanceOf( \Closure::class, $third[2] );
	}

	public function test_object_callbacks_for_hook_one_priority_one_hook() {

		$parsed = object_callbacks_for_hook( 'shutdown' );
		$parsed->rewind();

		$first  = $parsed->current();

		static::assertCount( 1, $parsed );

		static::assertSame( 55, $first[1] );
		static::assertInstanceOf( \Closure::class, $first[2] );
	}

	public function test_object_callbacks_for_hook_many_priorities_many_hook() {

		$parsed = object_callbacks_for_hook( 'the_title' );
		$parsed->rewind();

		static::assertCount( 6, $parsed );

		$first  = $parsed->current();
		$parsed->next();
		$second = $parsed->current();
		$parsed->next();
		$third = $parsed->current();
		$parsed->next();
		$fourth = $parsed->current();
		$parsed->next();
		$fifth = $parsed->current();
		$parsed->next();
		$sixth = $parsed->current();

		static::assertSame( 123, $first[1] );
		static::assertSame( 'assertTrue', $first[4] );

		static::assertSame( 123, $second[1] );
		static::assertSame( '__invoke', $second[4] );

		static::assertSame( 124, $third[1] );
		static::assertSame( 'another_public_method', $third[4] );

		static::assertSame( 124, $fourth[1] );
		static::assertSame( '__invoke', $fourth[4] );

		static::assertSame( 666, $fifth[1] );
		static::assertSame( 'a_public_method', $fifth[4] );

		static::assertSame( 666, $sixth[1] );
		static::assertSame( 'assertFalse', $sixth[4] );
	}

	public function test_object_callbacks_for_hook_given_priority_many_hook() {

		$parsed = object_callbacks_for_hook( 'the_title', 124 );
		$parsed->rewind();

		static::assertCount( 2, $parsed );

		$first  = $parsed->current();
		$parsed->next();
		$second = $parsed->current();

		static::assertSame( 124, $first[1] );
		static::assertSame( $this, $first[2] );
		static::assertSame( __CLASS__, $first[3] );
		static::assertSame( 'another_public_method', $first[4] );

		static::assertSame( 124, $second[1] );
		static::assertSame( '__invoke', $second[4] );
	}

	public function test_object_callbacks_no_added_hook() {

		$parsed = object_callbacks_for_hook( 'foo_bar' );

		static::assertCount( 0, $parsed );
	}
}