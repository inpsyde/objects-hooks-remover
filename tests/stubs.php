<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the objects-hooks-remover package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( ! function_exists( '_wp_filter_build_unique_id' ) ) {

	function _wp_filter_build_unique_id( $tag, $function, $priority = 10 ): string {

		if ( ! is_callable( $function ) || is_string( $function ) ) {
			throw new \PHPUnit\Framework\AssertionFailedError( 'This library targets hooks with object methods.' );
		}

		is_object( $function ) and $function = [ $function, '' ];

		$class_id = is_object( $function[ 0 ] ) ? spl_object_hash( $function[ 0 ] ) : "{$function[ 0 ]}::";

		return $class_id . $function[ 1 ];
	}
}

if ( ! function_exists( 'remove_filter' ) ) {

	function remove_filter( string $tag, $function, $priority = 10 ) {

		Inpsyde\Tests\TestCase::remove_filter( $tag, $function, $priority );
	}
}
