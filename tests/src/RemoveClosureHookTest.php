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

use function Inpsyde\remove_closure_hook;

class RemoveClosureHookTest extends TestCase {

	public function test_remove_not_added_hook() {

		$removed = remove_closure_hook( 'meh' );

		static::assertSame( 0, $removed );
	}

	public function test_remove_all_closures() {

		$removed = remove_closure_hook( 'the_title' );

		static::assertSame( 2, $removed );
		static::assertSame( [ 123, 124 ], $this->removedFilterHookPriorities() );
	}

	public function test_remove_only_static_closures() {

		$removed_the_title = remove_closure_hook( 'the_title', FALSE );

		$removed_template_redirect = remove_closure_hook( 'template_redirect', FALSE );

		static::assertSame( 0, $removed_the_title );
		static::assertSame( 1, $removed_template_redirect );
		static::assertSame( 42, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_closures_by_target_this_class() {

		$removed = remove_closure_hook( 'the_title', static::case_class() );

		static::assertSame( 1, $removed );
		static::assertSame( 124, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_closures_by_target_this_object() {

		$removed = remove_closure_hook( 'the_title', static::closure_bind() );

		static::assertSame( 1, $removed );
		static::assertSame( 123, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_closures_by_target_this_object_needs_instance_match() {

		$bind  = static::closure_bind();
		$clone = clone $bind;

		$removed = remove_closure_hook( 'the_title', $clone );

		static::assertSame( 0, $removed );
	}

	public function test_remove_closures_by_target_arg_names() {

		$removed_foo_bar = remove_closure_hook( 'the_title', null, [ '$foo', '$bar' ] );
		$removed_foo     = remove_closure_hook( 'the_title', null, [ '$foo' ] );

		static::assertSame( 0, $removed_foo_bar );
		static::assertSame( 1, $removed_foo );
		static::assertSame( 124, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_closures_by_target_args_names_and_type() {

		$removed_no = remove_closure_hook( 'template_redirect', null, [ '$foo' => 'array', '$bar' => 'string' ] );
		$removed_ok = remove_closure_hook( 'template_redirect', null, [ '$foo' => 'string', '$bar' => 'array' ] );

		static::assertSame( 0, $removed_no );
		static::assertSame( 1, $removed_ok );
	}

	public function test_remove_closures_by_target_this_and_args_and_priority() {

		$removed_no_1 = remove_closure_hook( 'the_title', self::closure_bind(), [ '$arg' => 'string' ], 123 );
		$removed_no_2 = remove_closure_hook( 'the_title', $this, [ '$arg' => 'int' ], 123 );
		$removed_no_3 = remove_closure_hook( 'the_title', self::closure_bind(), [ '$arg' => 'int' ], 124 );
		$removed_ok   = remove_closure_hook( 'the_title', self::closure_bind(), [ '$arg' => 'int' ], 123 );

		static::assertSame( 0, $removed_no_1 );
		static::assertSame( 0, $removed_no_2 );
		static::assertSame( 0, $removed_no_3 );
		static::assertSame( 1, $removed_ok );
	}
}