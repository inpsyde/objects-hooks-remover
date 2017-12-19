<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the objects-hooks-remover package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\ObjectHooksRemover;

if ( defined( __NAMESPACE__ . '\\SCALARS' ) ) {
	return;
}

const SCALARS = [
	'bool'    => 'bool',
	'boolean' => 'bool',
	'int'     => 'int',
	'integer' => 'int',
	'float'   => 'float',
	'double'  => 'float',
	'string'  => 'string',
	'array'   => 'array',
];

/**
 * Return information about callbacks added to given hook which have an object as callback.
 * If no priority is given, all priorities are taken into consideration.
 * Return an iterator where each element is a SplFixedArray with a size of 5.
 *
 * @see parse_callback_data() for info on the 5 values.
 *
 * @param string   $hook
 * @param int|null $priority
 *
 * @return \Iterator
 */
function object_callbacks_for_hook( string $hook, int $priority = null ): \Iterator {

	global $wp_filter;
	$callbacks_group = $wp_filter[ $hook ] ?? [];
	$all_callbacks   = [];

	// This is not for old WP compat, but in case this is called *very* early, before WP_Hook is even loaded.
	if ( $callbacks_group instanceof \WP_Hook ) {
		$all_callbacks = $callbacks_group->callbacks;
	} elseif ( is_array( $callbacks_group ) ) {
		$all_callbacks = $callbacks_group;
	}

	if ( ! $all_callbacks || ! is_array( $all_callbacks ) ) {
		return new \ArrayIterator();
	}

	$target_callbacks = is_int( $priority ) ? [ $priority => ( $all_callbacks[ $priority ] ?? [] ) ] : $all_callbacks;
	if ( ! $target_callbacks || ! is_array( $target_callbacks ) ) {
		return new \ArrayIterator();
	}

	$all = new \AppendIterator();
	foreach ( $target_callbacks as $callbacks_priority => $callbacks ) {
		$by_priority = array_filter(
			array_map(
				function ( $callback ) use ( $callbacks_priority ) {
					return parse_callback_data( $callback, $callbacks_priority );
				},
				$callbacks
			),
			'count'
		);
		$by_priority and $all->append( new \ArrayIterator( $by_priority ) );
	}

	return $all;
}

/**
 * Given hook callback data in the format used by WordPress hooks, and the related callback id return an SplFixedArray
 * with normalized callback data.
 *
 * If the callback do not contain an object (it is a plain named function) the returned SplFixedArray is empty,
 * otherwise it will have following indexes / values:
 *    [0] => (string) Callback id (will contain spl object hash for non-static callbacks)
 *    [1] => (int) Priority
 *    [2] => (object|null) Callback object or null in case of static callbacks
 *    [3] => (string) Callback object class name
 *    [4] => (string) Callback object method, always "__invoke" for both closures or invokable objects.
 *
 * @param array $callback
 * @param int   $priority
 *
 * @return \SplFixedArray
 */
function parse_callback_data( $callback, int $priority ): \SplFixedArray {

	if ( ! is_array( $callback ) ) {
		return new \SplFixedArray();
	}

	$function     = $callback[ 'function' ] ?? null;
	$is_invokable = $function && is_object( $function );

	/*
	 * WordPress does not check that callbacks added to hooks are actually callbacks.
	 * We skip invalid callbacks and named function callbacks.
	 */
	if ( ! is_callable( $function, FALSE ) || ! ( is_array( $function ) || $is_invokable ) ) {
		return new \SplFixedArray();
	}

	/*
	 * `$tag` and `$priority` params are required by `_wp_filter_build_unique_id` but only used for callbacks containing
	 * objects when `spl_object_hash` function doesn't exist, which is only possible with PHP <= 5.2 is some edge cases.
	 * Because we only support PHP 7+ we can happily ignore them.
	 */
	$callback_id = _wp_filter_build_unique_id( '', $function, 0 );

	// Static method.
	if ( ! $is_invokable && is_string( $function[ 0 ] ) ) {
		return \SplFixedArray::fromArray(
			[
				$callback_id,
				$priority,
				null,
				$function[ 0 ],
				$function[ 1 ]
			]
		);
	}

	// Dynamic method or closure/invokable object.
	return \SplFixedArray::fromArray(
		[
			$callback_id,
			$priority,
			$is_invokable ? $function : $function[ 0 ],
			$is_invokable ? get_class( $function ) : get_class( $function[ 0 ] ),
			$is_invokable ? '__invoke' : $function[ 1 ]
		]
	);
}

/**
 * Check if given object match given class name.
 * Check can be either exact or done against inheritance and implementation tree.
 * Use "@anonymous" to check if an object is an instance of an anonymous class.
 *
 * @param object|string $object
 * @param string        $class
 * @param bool          $exact
 *
 * @return bool
 */
function match_object_class( $object, string $class, bool $exact = FALSE ): bool {

	if ( ! is_object( $object ) && ! is_string( $object ) ) {
		return FALSE;
	}

	$object_class = is_string( $object ) ? $object : get_class( $object );

	if ( $class === '@anonymous' && strpos( $object_class, 'class@anonymous' ) === 0 ) {
		return TRUE;
	}

	if ( $exact ) {
		return $class === $object_class;
	}

	return is_a( $object_class, $class, TRUE );
}

/**
 * Matches a closure against a given `$this` context and given set of declared arguments.
 *
 * When both `$target_this` and `$target_args` are provided (!== null), both need to match for the function return
 * true.
 *
 * @param \Closure                   $closure        Closure to match.
 * @param string|object|boolean|null $target_this    Closure $this object or class/instance name.
 *                                                   It can take the shape of:
 *                                                    - `null`, default value, check for closure `$this` is skipped.
 *                                                    - `false`, will only match static closures.
 *                                                    - Object instance, will match only if closure `$this` matches
 *                                                      the given instance.
 *                                                    - String, valid class/instance name that needs to match closure
 *                                                      `$this` object class/instance name.
 * @param array|null                 $target_args    Closure arguments names.
 *                                                   It can take the shape of:
 *                                                    - `null` (default value), check for closure params is skipped.
 *                                                    - Numeric array of closure param **names**. For example:
 *                                                      `[ '$foo', '$bar', '$baz' ]`.
 *                                                    - Associative array where array keys are closure param **names**
 *                                                      and array values are closure param **types**. For example:
 *                                                      `['$foo' => 'int', '$bar' => '\Foo\ClassName', '$baz' => null]`.
 *                                                      In this form, `null` as array item value is used to match params
 *                                                      without type declaration.
 * @return bool True when there's a match.
 */
function match_closure( \Closure $closure, $target_this = null, array $target_args = null ): bool {

	if ( $target_this === null && $target_args === null ) {
		return TRUE;
	}

	// Ensure that `$target_this` is either `null`, `false`, an object, or a valid class/instance name.
	$is_object_target = $target_this && is_object( $target_this );
	if (
		! $is_object_target
		&& ! is_null( $target_this )
		&& $target_this !== FALSE
		&& ! ( is_string( $target_this ) && ( class_exists( $target_this ) || interface_exists( $target_this ) ) )
	) {
		return FALSE;
	}

	$reflection   = new \ReflectionFunction( $closure );
	$closure_this = $reflection->getClosureThis();
	$matched      = TRUE;

	/*
	 * If `$target_this` was provided as false, only matches static closures.
	 */
	if ( $target_this === FALSE ) {
		return $closure_this === null;
	}

	/*
	 * If `$target_this` was provided, it must either match closure `$this` instance or pass is_a check against it,
	 * depending if, in order, it was provided as an object or a string.
	 */
	if ( $target_this ) {
		$matched =
			$is_object_target && $closure_this === $target_this
			|| ! $is_object_target && match_object_class( $closure_this, $target_this );
	}

	// If `$this` check did not passed, then return false; if it matched and no `$target_args`, then return true.
	if ( ! $matched || $target_args === null ) {
		return $matched;
	}

	// If number of arguments did not matches return false.
	$closure_args_num = $reflection->getNumberOfParameters();
	if ( $closure_args_num !== count( $target_args ) ) {
		return FALSE;
	}

	// If number of arguments matched, and it was 0, then just return true: nothing to check.
	if ( ! $closure_args_num ) {
		return TRUE;
	}

	// Let's check argument one by one...

	$args = $reflection->getParameters();

	// When `$target_args` is an array of strings without keys, we are going to ignore args type and just look at names.
	$ignore_type =
		! array_filter( array_keys( $target_args ), 'is_string' )
		&& array_filter( $target_args, 'is_string' ) === $target_args;

	$ignore_type and $target_args = array_fill_keys( $target_args, null );

	$index = 0;
	foreach ( $target_args as $name => $type ) {

		// Sanity check on `$name` requirement.
		if ( ! is_string( $name ) || ( $name[ 0 ] ?? '' ) !== '$' ) {
			return FALSE;
		}

		/** @var \ReflectionParameter $param */
		$param = $args[ $index ];
		$index ++;

		// If param name does not match, return false;
		if ( '$' . $param->name !== $name ) {
			return FALSE;
		}

		// If param type is ignored and name already matched, check passed for this param.
		if ( $ignore_type ) {
			continue;
		}

		// Sanity check on `$type` requirement.
		is_null( $type ) and $type = 'null';
		if ( ! $type || ! is_string( $type ) ) {
			return FALSE;
		}

		$param_type = $param->getType();

		// If declaration parameter has no type, given argument must be "null".
		if ( ! $param_type && strtolower( $type ) !== 'null' ) {
			return FALSE;
		}

		// If declaration parameter has no type and given argument is "null", check passed for this param.
		if ( ! $param_type ) {
			continue;
		}

		$param_type_name = (string) $param_type;

		// Match the type or return false.
		$match = in_array( $param_type_name, SCALARS, TRUE )
			? $param_type_name === ( SCALARS[ strtolower( $type ) ] ?? null )
			: $param_type_name === ltrim( $type, '\\' );

		if ( ! $match ) {
			return FALSE;
		}
	}

	return TRUE;
}