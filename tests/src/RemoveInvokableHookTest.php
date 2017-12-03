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

use function Inpsyde\remove_invokable_hook;

class RemoveInvokableHookTest extends TestCase {

	public function test_remove_invokable_check_class() {

		static::assertSame( 0, remove_invokable_hook( 'template_redirect', __CLASS__ ) );
	}

	public function test_remove_invokable() {

		static::assertSame( 1, remove_invokable_hook( 'template_redirect', '@anonymous' ) );
		static::assertSame( 4242, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_closures_as_invokable() {

		static::assertSame( 1, remove_invokable_hook( 'template_redirect', \Closure::class ) );
		static::assertSame( 42, $this->lastRemovedFilterHookPriority() );
	}

	public function test_remove_invokable_by_class_and_priority() {

		static::assertSame( 0, remove_invokable_hook( 'template_redirect', '@anonymous', 42 ) );
		static::assertSame( 1, remove_invokable_hook( 'template_redirect', '@anonymous', 4242 ) );
		static::assertSame( 1, remove_invokable_hook( 'template_redirect', \Closure::class, 42 ) );
		static::assertSame( 0, remove_invokable_hook( 'template_redirect', \Closure::class, 4242 ) );
	}
}