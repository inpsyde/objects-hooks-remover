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

use function Inpsyde\ObjectHooksRemover\match_object_class;

class MatchObjectClassTest extends TestCase {

	public function test_match_object() {

		static::assertTrue( match_object_class( $this, __CLASS__ ) );
	}

	public function test_match_class() {

		static::assertTrue( match_object_class( __CLASS__, __CLASS__ ) );
	}

	public function test_match_object_inheritance() {

		static::assertTrue( match_object_class( $this, TestCase::class ) );
	}

	public function test_match_object_interface() {

		static::assertTrue( match_object_class( new \ArrayObject(), \ArrayAccess::class ) );
	}

	public function test_match_class_inheritance() {

		static::assertTrue( match_object_class( __CLASS__, TestCase::class ) );
	}

	public function test_match_class_interface() {

		static::assertTrue( match_object_class( \ArrayObject::class, \ArrayAccess::class ) );
	}

	public function test_match_object_inheritance_fails_when_exact() {

		static::assertFalse( match_object_class( $this, TestCase::class, TRUE ) );
	}

	public function test_match_object_interface_fails_when_exact() {

		static::assertFalse( match_object_class( new \ArrayObject(), \ArrayAccess::class, TRUE ) );
	}

	public function test_match_class_inheritance_fails_when_exact() {

		static::assertFalse( match_object_class( __CLASS__, TestCase::class, true ) );
	}

	public function test_match_class_interface_fails_when_exact() {

		static::assertFalse( match_object_class( \ArrayObject::class, \ArrayAccess::class, true ) );
	}

	public function test_match_closure() {

		$f = function () {
		};

		static::assertTrue( match_object_class( $f, \Closure::class ) );
	}

	public function test_match_anonymous() {

		$c = new class {

		};

		static::assertTrue( match_object_class( $c, '@anonymous' ) );
	}

	public function test_match_anonymous_three() {

		$c = new class extends \ArrayObject implements \ArrayAccess {

		};

		static::assertTrue( match_object_class( $c, '@anonymous' ) );
		static::assertTrue( match_object_class( $c, '@anonymous', TRUE ) );
		static::assertTrue( match_object_class( $c, \ArrayAccess::class ) );
		static::assertTrue( match_object_class( $c, \ArrayObject::class ) );
		static::assertFalse( match_object_class( $c, \ArrayAccess::class, TRUE ) );
		static::assertFalse( match_object_class( $c, \ArrayObject::class, TRUE ) );
	}
}