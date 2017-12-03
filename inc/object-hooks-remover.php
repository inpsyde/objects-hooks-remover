<?php declare( strict_types=1 ); # -*- coding: utf-8 -*-
/*
 * This file is part of the objects-hooks-remover package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde;

if ( defined( __NAMESPACE__ . '\\OBJECT_HOOKS_REMOVER_VER' ) ) {
	return;
}

const OBJECT_HOOKS_REMOVER_VER = '0.1';

/**
 * Remove callbacks added to given hook using objects of given class name.
 * If no priority is given, hooks added to any priority are removed.
 * By default, removes only dynamic object methods, will also remove static methods when last argument is set to true.
 *
 * Note that a callbacks will be considered dynamic if the hook was added using an object *instance*, no matter if
 * the method of the class is declared as static.
 *
 * @param string      $hook                    Action or filter name to remove.
 * @param string      $class_name              Class or interface name of the hook callbacks to remove.
 * @param string|null $method_name             Method name of the hook callbacks to remove.
 * @param int         $priority                Priority to target, null to target all of them.
 * @param bool        $remove_static_callbacks When false (default) will not remove static methods.
 *
 * @return int Number of removed hooks.
 */
function remove_object_hook(
	string $hook,
	string $class_name,
	string $method_name = null,
	int $priority = null,
	bool $remove_static_callbacks = FALSE
): int {

	$callbacks = ObjectHooksRemover\object_callbacks_for_hook( $hook, $priority );
	$callbacks->rewind();

	if ( ! $callbacks->valid() ) {
		return 0;
	}

	$removed = 0;

	foreach ( $callbacks as list( $idx, $priority, $object, $class, $method ) ) {

		$object_match = $remove_static_callbacks || is_object( $object );

		if (
			( $object_match && ObjectHooksRemover\match_object_class( $class, $class_name ) )
			&& ( ! $method_name || $method === $method_name )
		) {
			remove_filter( $hook, $idx, $priority );
			$removed ++;
		}
	}

	return $removed;
}

/**
 * Similar to `remove_object_hook()` remove **only** static methods callbacks.
 *
 * @param string      $hook        Action or filter name to remove.
 * @param string      $class_name  Class or interface name of the hook callbacks to remove.
 * @param string|null $method_name Method name of the hook callbacks to remove.
 * @param int         $priority    Priority to target, null to target all of them.
 *
 * @return int Number of removed hooks.
 */
function remove_class_hook( string $hook, string $class_name, string $method_name = null, int $priority = null ): int {

	$callbacks = ObjectHooksRemover\object_callbacks_for_hook( $hook, $priority );
	$callbacks->rewind();

	if ( ! $callbacks->valid() ) {
		return 0;
	}

	$removed = 0;

	foreach ( $callbacks as list( $idx, $priority, $object, $class, $method ) ) {

		if (
			is_null( $object )
			&& ObjectHooksRemover\match_object_class( $class, $class_name )
			&& ( ! $method_name || $method === $method_name )
		) {
			remove_filter( $hook, $idx, $priority );
			$removed ++;
		}
	}

	return $removed;
}

/**
 * Remove callbacks added to given hook using invokable objects of given class name or extending/implementing given
 * class/interface name.
 * If no priority is given, hooks added to any priority are removed.
 *
 * @param string   $hook       Action or filter name to remove.
 * @param string   $class_name Class name of the invokable object to remove callbacks for.
 * @param int|null $priority   Priority to target, null to target all of them.
 *
 * @return int Number of removed hooks.
 */
function remove_invokable_hook( string $hook, string $class_name, int $priority = null ): int {

	return remove_object_hook( $hook, $class_name, '__invoke', $priority, FALSE );
}

/**
 * Remove callbacks added to given hook using object of given instance (no matter method name).
 * If no priority is given, hooks added to any priority are removed.
 *
 * @param string   $hook            Action or filter name to remove.
 * @param object   $object_instance Object instance of the hook callbacks to remove.
 * @param int|null $priority        Priority to target, null to target all of them.
 *
 * @return int Number of removed hooks.
 */
function remove_instance_hook( string $hook, $object_instance, int $priority = null ): int {

	$callbacks = ObjectHooksRemover\object_callbacks_for_hook( $hook, $priority );
	$callbacks->rewind();

	if ( ! $callbacks->valid() ) {
		return 0;
	}

	$removed = 0;
	foreach ( $callbacks as list( $idx, $priority, $object ) ) {
		if ( $object === $object_instance ) {
			remove_filter( $hook, $idx, $priority );
			$removed ++;
		}
	}

	return $removed;
}

/**
 * Remove closure callbacks added to given hook.
 * Via the second param `$closure_this` callbacks to remove can be limited by filtering them via the value of their
 * `$this` context. Target `$this`context can be given as object (strict matching applied) or as class name.
 * If no priority is given, all priorities are taken into consideration.
 *
 * @param string                   $hook        Action or filter name to remove.
 * @param string|object|null|false $target_this Used to filter closures based on `$this` context.
 * @param array|null               $target_args Used to filter closures based on declared arguments.
 * @param int|null                 $priority    Priority to target, null to target all of them.
 *
 * @return int Number of removed hooks.
 *
 * @see ObjectHooksRemover\match_closure() for how to use `$target_this` and/or `$target_args` to filter closures.
 */
function remove_closure_hook(
	string $hook,
	$target_this = null,
	array $target_args = null,
	int $priority = null
): int {

	$callbacks = ObjectHooksRemover\object_callbacks_for_hook( $hook, $priority );
	$callbacks->rewind();

	if ( ! $callbacks->valid() ) {
		return 0;
	}

	if ( $target_this === null && $target_args === null ) {
		return remove_invokable_hook( $hook, \Closure::class, $priority );
	}

	$removed = 0;
	foreach ( $callbacks as list( $idx, $priority, $object, $class ) ) {

		if ( $class === \Closure::class && ObjectHooksRemover\match_closure( $object, $target_this, $target_args ) ) {
			remove_filter( $hook, $idx, $priority );
			$removed ++;
		}
	}

	return $removed;
}