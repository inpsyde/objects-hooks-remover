<?php declare( strict_types = 1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the objects-hooks-remover package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Tests;

use function Inpsyde\remove_class_hook;

class RemoveClassHookTest extends TestCase {

	public function test_remove_not_added_hook() {

		$removed = remove_class_hook( 'meh', self::case_class() );

		static::assertSame( 0, $removed );
	}

	public function test_dynamic_methods_are_not_removed() {

		$removed = remove_class_hook( 'the_title', self::case_class(), 'a_public_method', 124 );

		static::assertSame( 0, $removed );
	}

	public function test_static_methods_are_removed_even_not_provided() {

		$removed = remove_class_hook( 'init', self::case_class() );

		static::assertSame( 1, $removed );
	}

	public function test_specific_static_method() {

		$removed = remove_class_hook( 'the_title', self::case_class(), 'assertFalse' );

		static::assertSame( 1, $removed );
		static::assertSame( 666, $this->lastRemovedFilterHookPriority() );
	}

	public function test_specific_static_method_specific_priority() {

		$removed = remove_class_hook( 'the_title', self::case_class(), 'assertFalse', 666 );

		static::assertSame( 1, $removed );
		static::assertSame( 666, $this->lastRemovedFilterHookPriority() );
	}

	public function test_specific_static_method_specific_priority_not_found() {

		$removed = remove_class_hook( 'the_title', self::case_class(), 'assertTrue', 666 );

		static::assertSame( 0, $removed );
	}

	public function test_specific_all_methods_specific_priority() {

		$removed = remove_class_hook( 'the_title', self::case_class(), null, 123 );

		static::assertSame( 1, $removed );
		static::assertSame( 123, $this->lastRemovedFilterHookPriority() );
	}

}