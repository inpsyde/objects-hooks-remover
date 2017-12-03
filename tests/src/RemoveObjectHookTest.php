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

use function Inpsyde\remove_object_hook;

class RemoveObjectHookTest extends TestCase {

	public function test_remove_not_added_hook() {

		$removed = remove_object_hook( 'foo', static::case_class() );

		static::assertSame( 0, $removed );
	}

	public function test_remove_dynamic() {

		$removed = remove_object_hook( 'init', static::case_class() );

		static::assertSame( 1, $removed );
		static::assertSame( 10, $this->lastRemovedFilterHookPriority() );
		static::assertSame( 'init', $this->lastRemovedFilterHookTag() );
	}

	public function test_remove_dynamic_and_static() {

		$removed = remove_object_hook( 'init', static::case_class(), null, null, TRUE );

		static::assertSame( 2, $removed );
		static::assertSame( [ 10, 10 ], $this->removedFilterHookPriorities() );
		static::assertSame( [ 'init', 'init' ], $this->removedFilterHookTags() );
	}

	public function test_remove_dynamic_and_static_specific_method() {

		$removed = remove_object_hook( 'init', static::case_class(), 'a_public_method', null, TRUE );

		static::assertSame( 1, $removed );
		static::assertSame( 10, $this->lastRemovedFilterHookPriority() );
		static::assertSame( 'init', $this->lastRemovedFilterHookTag() );
	}

	public function test_remove_specific_static_method_do_nothing_if_static_not_allowed() {

		$removed = remove_object_hook( 'init', static::case_class(), 'assertTrue' );

		static::assertSame( 0, $removed );
	}

	public function test_remove_specific_static_method_do_remove_if_static_allowed() {

		$removed = remove_object_hook( 'init', static::case_class(), 'assertTrue', null, TRUE );

		static::assertSame( 1, $removed );
		static::assertSame( 10, $this->lastRemovedFilterHookPriority() );
		static::assertSame( 'init', $this->lastRemovedFilterHookTag() );
	}

	public function test_remove_dynamic_multi_priority() {

		$removed = remove_object_hook( 'the_title', static::case_class() );

		static::assertSame( 2, $removed );
		static::assertSame( [ 124, 666 ], $this->removedFilterHookPriorities() );
	}

	public function test_remove_dynamic_and_static_multi_priority() {

		$removed = remove_object_hook( 'the_title', static::case_class(), null, null, TRUE );

		static::assertSame( 4, $removed );
		static::assertSame( [ 123, 124, 666, 666 ], $this->removedFilterHookPriorities() );
	}

	public function test_remove_dynamic_and_static_specific_method_from_multi_priority_hook() {

		$removed = remove_object_hook( 'the_title', static::case_class(), 'a_public_method', null, TRUE );

		static::assertSame( 1, $removed );
		static::assertSame( 666, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_dynamic_and_static_specific_priority_from_multi_priority_hook() {

		$removed = remove_object_hook( 'the_title', static::case_class(), null, 123, TRUE );

		static::assertSame( 1, $removed );
		static::assertSame( [ 123 ], $this->removedFilterHookPriorities() );
	}

	public function test_remove_dynamic_multi_priority_specific_priority_from_multi_priority_hook() {

		$removed = remove_object_hook( 'the_title', static::case_class(), null, 123 );

		static::assertSame( 0, $removed );
	}

	public function test_remove_dynamic_specific_priority_specific_method_from_multi_priority_hook() {

		$removed = remove_object_hook( 'the_title', static::case_class(), 'assertTrue', 123 );

		static::assertSame( 0, $removed );
	}

	public function test_remove_dynamic_and_static_specific_priority_specific_method_from_multi_priority_hook() {

		$removed = remove_object_hook( 'the_title', static::case_class(), 'assertTrue', 123, TRUE );

		static::assertSame( 1, $removed );
		static::assertSame( [ 123 ], $this->removedFilterHookPriorities() );
	}

	public function test_remove_anonymous_by_class() {

		$removed = remove_object_hook( 'template_redirect', '@anonymous' );

		static::assertSame( 1, $removed );
		static::assertSame( 4242, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_anonymous_by_class_and_method() {

		$removed = remove_object_hook( 'template_redirect', '@anonymous', '__invoke' );

		static::assertSame( 1, $removed );
		static::assertSame( 4242, $this->lastRemovedFilterHookPriority() );
	}
}