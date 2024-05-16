<?php

declare(strict_types=1);

namespace Inpsyde;

// phpcs:disable PSR1.Files.SideEffects

if (defined(__NAMESPACE__ . '\\OBJECT_HOOKS_REMOVER_VER')) {
    return;
}

const OBJECT_HOOKS_REMOVER_VER = '1.0.0';

/**
 * Remove callbacks added to given hook using objects of given class name.
 *
 * If no priority is given, hooks added to any priority are removed.
 * By default, removes only dynamic object methods, will also remove static methods when last
 * argument is set to true.
 *
 * Note that a callbacks will be considered dynamic if the hook was added using an object instance,
 * no matter if the method of the class is static.
 *
 * @param string $hook Action or filter name to remove.
 * @param string $targetClassName Class or interface name of the hook callbacks to remove.
 * @param string|null $targetMethodName Method name of the hook callbacks to remove.
 * @param int|null $targetPriority Priority to target, null to target all of them.
 * @param bool $removeStaticCallbacks When false (default) will not remove static methods.
 * @return int Number of removed hooks.
 */
function remove_object_hook(
    string $hook,
    string $targetClassName,
    ?string $targetMethodName = null,
    ?int $targetPriority = null,
    bool $removeStaticCallbacks = false
): int {

    return ObjectHooksRemover\Functions::removeObjectHook(
        $hook,
        $targetClassName,
        $targetMethodName,
        $targetPriority,
        $removeStaticCallbacks
    );
}

/**
 * Similar to `remove_object_hook()` remove only static methods callbacks.
 *
 * @param string $hook Action or filter name to remove.
 * @param string $targetClassName Class or interface name of the hook callbacks to remove.
 * @param string|null $targetMethodName Method name of the hook callbacks to remove.
 * @param int|null $targetPriority Priority to target, null to target all of them.
 * @return int Number of removed hooks.
 */
function remove_static_method_hook(
    string $hook,
    string $targetClassName,
    ?string $targetMethodName = null,
    ?int $targetPriority = null
): int {

    return ObjectHooksRemover\Functions::removeStaticMethodHook(
        $hook,
        $targetClassName,
        $targetMethodName,
        $targetPriority
    );
}

/**
 * Remove callbacks added to given hook using invokable objects of given class name or
 * extending/implementing given class/interface name.
 *
 * If no priority is given, hooks added to any priority are removed.
 *
 * @param string $hook Action or filter name to remove.
 * @param string $targetClassName Class name of the invokable object to remove callbacks for.
 * @param int|null $targetPriority Priority to target, null to target all of them.
 * @return int Number of removed hooks.
 */
function remove_invokable_hook(
    string $hook,
    string $targetClassName,
    ?int $targetPriority = null
): int {

    return remove_object_hook($hook, $targetClassName, '__invoke', $targetPriority);
}

/**
 * Remove callbacks added to given hook using object of given instance (no matter method name).
 *
 * If no priority is given, hooks added to any priority are removed.
 *
 * @param string $hook Action or filter name to remove.
 * @param object $targetObject Object instance of the hook callbacks to remove.
 * @param int|null $targetPriority Priority to target, null to target all of them.
 * @return int Number of removed hooks.
 */
function remove_instance_hook(string $hook, object $targetObject, ?int $targetPriority = null): int
{
    return ObjectHooksRemover\Functions::removeInstanceHook($hook, $targetObject, $targetPriority);
}

/**
 * Remove closure callbacks added to given hook.
 *
 * Via the second param `$targetThis` callbacks to remove can be limited by filtering them via
 * the value of their `$this` context.
 * Target `$this`context can be given as object (strict matching applied) or as class name.
 * If no priority is given, all priorities are taken into consideration.
 *
 * @param string $hook Action or filter name to remove.
 * @param string|object|null|false $targetThis Used to filter closures based on `$this` context.
 * @param array|null $targetArgs Used to filter closures based on declared arguments.
 * @param int|null $targetPriority Priority to target, null to target all of them.
 * @return int Number of removed hooks.
 */
function remove_closure_hook(
    string $hook,
    $targetThis = null,
    ?array $targetArgs = null,
    ?int $targetPriority = null
): int {

    return ObjectHooksRemover\Functions::removeClosureHook(
        $hook,
        $targetThis,
        $targetArgs,
        $targetPriority
    );
}

/**
 * Remove all hook callbacks that use given object or class.
 *
 * @param class-string|object $object
 * @param bool|null $removeStaticCallbacks
 * @return int
 */
function remove_all_object_hooks($object, ?bool $removeStaticCallbacks = null): int
{
    return ObjectHooksRemover\Functions::removeAllObjectHooks($object, $removeStaticCallbacks);
}

/**
 * @param string $hook Action or filter name to remove.
 * @param string $targetClassName Class or interface name of the hook callbacks to remove.
 * @param string|null $targetMethodName Method name of the hook callbacks to remove.
 * @param int|null $targetPriority Priority to target, null to target all of them.
 * @return int Number of removed hooks.
 *
 * @deprecated Use remove_static_method_hook() instead
 * @codeCoverageIgnore
 */
function remove_class_hook(
    string $hook,
    string $targetClassName,
    ?string $targetMethodName = null,
    ?int $targetPriority = null
): int {

    return remove_static_method_hook(
        $hook,
        $targetClassName,
        $targetMethodName,
        $targetPriority
    );
}
