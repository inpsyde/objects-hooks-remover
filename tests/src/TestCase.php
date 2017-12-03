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

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package object-hooks-remover
 * @license http://opensource.org/licenses/MIT MIT
 */
abstract class TestCase extends PHPUnitTestCase {

	private static $closure_bind;

	/**
	 * @return string
	 */
	public static function case_class(): string {
		return __CLASS__;
	}

	/**
	 * @return \stdClass
	 */
	public static function closure_bind(): \stdClass {

		self::$closure_bind or self::$closure_bind = new \stdClass();

		return self::$closure_bind;
	}

	/**
	 * Used to stub WP function and make it testable.
	 *
	 * @param string $tag
	 * @param string $callback_id
	 * @param int    $priority
	 *
	 * @return void
	 */
	public static function remove_filter( string $tag, string $callback_id, $priority = 10 ) {

		global $removed_filters;
		is_array( $removed_filters ) or $removed_filters = [];
		$removed_filters[] = (object) compact( 'tag', 'callback_id', 'priority' );
	}

	/**
	 * @return void
	 */
	public function a_public_method() {
	}

	/**
	 * @return void
	 */
	public function another_public_method() {
	}

	/**
	 * Set various hooks...
	 */
	protected function setUp() {

		parent::setUp();

		$cl_1 = \Closure::bind( function ( int $arg ) {

		}, self::closure_bind() );

		$cl_2 = function ( $foo ) {

		};

		$cl_3 = static function ( string $foo, array $bar ) {

		};

		$invokable = new class {

			public function __invoke() {

			}
		};

		global $wp_filter, $removed_filters;
		$removed_filters = [];
		$wp_filter       = [
			'init'              => [
				10 => [
					[ 'function' => [ $this, 'a_public_method' ] ],
					[ 'function' => 'strtoupper' ],
					[ 'function' => [ __CLASS__, 'assertTrue' ] ],
					[ 'function' => $cl_2 ],
				]
			],
			'template_redirect' => [
				42   => [
					[ 'function' => $cl_3 ],
				],
				4242 => [
					[ 'function' => $invokable ],
				]
			],
			'shutdown'          => [
				55 => [
					[ 'function' => $cl_1 ],
				]
			],
			'the_title'         => [
				123 => [
					[ 'function' => 'strtolower' ],
					[ 'function' => [ __CLASS__, 'assertTrue' ] ],
					[ 'function' => $cl_1 ],
				],
				124 => [
					[ 'function' => [ $this, 'another_public_method' ] ],
					[ 'function' => 'strtoupper' ],
					[ 'function' => $cl_2 ],
				],
				666 => [
					[ 'function' => [ $this, 'a_public_method' ] ],
					[ 'function' => [ __CLASS__, 'assertFalse' ] ],
				]
			]
		];
	}

	/**
	 * Clean up hooks...
	 */
	protected function tearDown() {

		global $removed_filters, $wp_filter;
		unset( $removed_filters, $wp_filter );

		parent::tearDown();
	}

	/**
	 * @return string[]
	 */
	protected function removedFilterHookTags(): array {

		global $removed_filters;

		return $removed_filters ? array_column( $removed_filters, 'tag' ) : [];
	}

	/**
	 * @return int[]
	 */
	protected function removedFilterHookPriorities(): array {

		global $removed_filters;

		return $removed_filters ? array_column( $removed_filters, 'priority' ) : [];
	}

	/**
	 * @return string|null
	 */
	protected function lastRemovedFilterHookTag() {

		global $removed_filters;

		return $removed_filters ? reset( $removed_filters )->tag : null;
	}

	/**
	 * @return string|null
	 */
	protected function lastRemovedFilterHookId() {

		global $removed_filters;

		return $removed_filters ? reset( $removed_filters )->callback_id : null;
	}

	/**
	 * @return int|null
	 */
	protected function lastRemovedFilterHookPriority() {

		global $removed_filters;

		return $removed_filters ? reset( $removed_filters )->priority : null;
	}

}