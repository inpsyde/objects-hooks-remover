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

use function Inpsyde\remove_instance_hook;

class RemoveInstanceHookTest extends TestCase {

	public function test_remove_nothing() {

		self::assertSame( 0, remove_instance_hook( 'template_redirect', $this ) );
	}

	public function test_remove_all() {

		self::assertSame( 2, remove_instance_hook( 'the_title', $this ) );
		self::assertSame( [ 124, 666 ], $this->removedFilterHookPriorities() );
	}

	public function test_remove_by_priority() {

		self::assertSame( 0, remove_instance_hook( 'the_title', $this, 123 ) );
		self::assertSame( 1, remove_instance_hook( 'the_title', $this, 666 ) );
	}
}