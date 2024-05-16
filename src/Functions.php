<?php

declare(strict_types=1);

namespace Inpsyde\ObjectHooksRemover;

/** @internal */
abstract class Functions
{
    /**
     * @param string $hook
     * @param string $targetClassName
     * @param string|null $targetMethodName
     * @param int|null $targetPriority
     * @param bool $removeStaticCallbacks
     * @return int Number of removed hooks.
     */
    final public static function removeObjectHook(
        string $hook,
        string $targetClassName,
        ?string $targetMethodName = null,
        ?int $targetPriority = null,
        bool $removeStaticCallbacks = false
    ): int {

        $callbacks = static::objectCallbacksForHook($hook, $targetPriority);
        if ($callbacks === []) {
            return 0;
        }

        $removed = 0;

        foreach ($callbacks as [$idx, $targetPriority, $object, $class, $method]) {
            $objectMatch = $removeStaticCallbacks || is_object($object);
            if (
                ($objectMatch && static::matchObjectClass($class, $targetClassName))
                && (($targetMethodName === null) || ($method === $targetMethodName))
            ) {
                remove_filter($hook, $idx, $targetPriority);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param string $hook
     * @param string $targetClassName
     * @param string|null $targetMethodName
     * @param int|null $targetPriority
     * @return int
     */
    final public static function removeStaticMethodHook(
        string $hook,
        string $targetClassName,
        ?string $targetMethodName = null,
        ?int $targetPriority = null
    ): int {

        $callbacks = static::objectCallbacksForHook($hook, $targetPriority);
        if ($callbacks === []) {
            return 0;
        }

        $removed = 0;

        foreach ($callbacks as [$idx, $targetPriority, $object, $class, $method]) {
            if (
                is_null($object)
                && static::matchObjectClass($class, $targetClassName, true)
                && (($targetMethodName === null) || ($method === $targetMethodName))
            ) {
                remove_filter($hook, $idx, $targetPriority);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param string $hook Action or filter name to remove.
     * @param object $targetObject Object instance of the hook callbacks to remove.
     * @param int|null $targetPriority Priority to target, null to target all of them.
     * @return int Number of removed hooks.
     */
    final public static function removeInstanceHook(
        string $hook,
        object $targetObject,
        ?int $targetPriority = null
    ): int {

        $callbacks = static::objectCallbacksForHook($hook, $targetPriority);
        if ($callbacks === []) {
            return 0;
        }

        $removed = 0;
        foreach ($callbacks as [$idx, $targetPriority, $object]) {
            if ($object === $targetObject) {
                remove_filter($hook, $idx, $targetPriority);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param string $hook Action or filter name to remove.
     * @param string|object|null|false $targetThis Used to filter closures based on `$this` context.
     * @param array|null $targetArgs Used to filter closures based on declared arguments.
     * @param int|null $targetPriority Priority to target, null to target all of them.
     * @return int Number of removed hooks.
     */
    final public static function removeClosureHook(
        string $hook,
        $targetThis = null,
        ?array $targetArgs = null,
        ?int $targetPriority = null
    ): int {

        $callbacks = static::objectCallbacksForHook($hook, $targetPriority);
        if ($callbacks === []) {
            return 0;
        }

        if (($targetThis === null) && ($targetArgs === null)) {
            return static::removeObjectHook($hook, \Closure::class, '__invoke', $targetPriority);
        }

        $removed = 0;
        foreach ($callbacks as [$idx, $targetPriority, $object, $class]) {
            /** @psalm-suppress InvalidArgument */
            if (
                ($class === \Closure::class)
                && static::matchClosure($object, $targetThis, $targetArgs)
            ) {
                remove_filter($hook, $idx, $targetPriority);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @param mixed $object
     * @param bool|null $removeStaticCallbacks
     * @return int
     */
    final public static function removeAllObjectHooks($object, ?bool $removeStaticCallbacks): int
    {
        global $wp_filter;
        if (!is_array($wp_filter)) {
            return 0;
        }

        $isClass = ($object !== 'object') && self::isClassLikeString($object);
        if (!is_object($object) && !$isClass) {
            return 0;
        }

        /** @var class-string|object $object */

        $removed = 0;
        foreach (array_keys($wp_filter) as $hook) {
            if (!is_string($hook)) {
                continue;
            }
            $removed += static::removeAllObjectCallbacks(
                $hook,
                $object,
                $isClass,
                $removeStaticCallbacks
            );
        }

        return $removed;
    }

    /**
     * @param string $hook
     * @param class-string|object $object
     * @param bool $isClass
     * @param bool|null $removeStaticCallbacks
     * @return int
     */
    private static function removeAllObjectCallbacks(
        string $hook,
        $object,
        bool $isClass,
        ?bool $removeStaticCallbacks
    ): int {

        $targetClass = $isClass ? $object : null;
        $removeStaticCallbacks ??= $isClass;
        $callbacks = static::objectCallbacksForHook($hook);

        $removed = 0;
        foreach ($callbacks as [$id, $priority, $callbackObject, $callbackClass]) {
            $isStatic = $callbackObject === null;
            if ($isStatic && !$removeStaticCallbacks) {
                continue;
            }

            /** @psalm-suppress PossiblyInvalidArgument */
            $isStatic and $targetClass ??= get_class($object);

            $matched = $isStatic
                ? ($targetClass === $callbackClass)
                : ($object === ($isClass ? $callbackClass : $callbackObject));

            if ($matched) {
                $removed++;
                remove_filter($hook, $id, $priority);
            }
        }

        return $removed;
    }

    /**
     * @param string $hook
     * @param int|null $targetPriority
     * @return list<list{non-empty-string, int, object|null, class-string, non-empty-string}>
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private static function objectCallbacksForHook(string $hook, ?int $targetPriority = null): array
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        if ($hook === '') {
            return [];
        }

        global $wp_filter;
        $callbacksGroup = is_array($wp_filter) ? ($wp_filter[$hook] ?? []) : [];
        $allCallbacks = [];

        // This is not for old WP compat, but in case this is called *very* early, before WP_Hook is
        // even loaded.
        if ($callbacksGroup instanceof \WP_Hook) {
            $allCallbacks = $callbacksGroup->callbacks;
        } elseif (is_array($callbacksGroup)) {
            $allCallbacks = $callbacksGroup;
        }

        /** @psalm-suppress DocblockTypeContradiction */
        if (($allCallbacks === []) || !is_array($allCallbacks)) {
            return [];
        }
        $targetCallbacks = ($targetPriority === null)
            ? $allCallbacks
            : [$targetPriority => ($allCallbacks[$targetPriority] ?? [])];

        /** @psalm-suppress TypeDoesNotContainType */
        if (($targetCallbacks === []) || !is_array($targetCallbacks)) {
            return [];
        }

        $all = [];
        foreach ($targetCallbacks as $priority => $callbacks) {
            if (is_array($callbacks)) {
                $all = static::collectObjectCallbacksByPriority($callbacks, (int) $priority, $all);
            }
        }

        return $all;
    }

    /**
     * @param array $callbacks
     * @param int $priority
     * @param list<list{non-empty-string, int, object|null, class-string, non-empty-string}> $all
     * @return list<list{non-empty-string, int, object|null, class-string, non-empty-string}>
     */
    private static function collectObjectCallbacksByPriority(
        array $callbacks,
        int $priority,
        array $all = []
    ): array {

        foreach ($callbacks as $callback) {
            [$id, $targetThis, $class, $method] = static::parseCallbackData($callback);
            if (($class !== '') && ($id !== '') && ($method !== '')) {
                $all[] = [$id, $priority, $targetThis, $class, $method];
            }
        }

        return $all;
    }

    /**
     * @param mixed $callbackData
     * @return list{string, object|null, class-string|"", string}
     */
    private static function parseCallbackData($callbackData): array
    {
        if (!is_array($callbackData)) {
            return ['', null, '', ''];
        }

        $callback = $callbackData['function'] ?? null;

        // 2nd param `true` means syntax-only check
        if (!is_callable($callback, true)) {
            return ['', null, '', ''];
        }

        if (is_string($callback)) {
            if (substr_count($callback, '::') !== 1) {
                // We are not interested in plain string function names
                return ['', null, '', ''];
            }

            // Static method passed as string
            $callback = explode('::', $callback);
        }

        $isInvokable = is_object($callback);

        /** @var list{string|object, string}|object $callback */

        /**
         * `is_callable($cb, true)` returns `true` for empty class or empty method.
         * Moreover, we catch here things like "Foo::", "::foo", or "::" passed as callback.
         * @psalm-suppress PossiblyInvalidArrayAccess
         */
        if (!$isInvokable && (($callback[0] === '') || (($callback[1] === '')))) {
            return ['', null, '', ''];
        }

        /*
         * 1st and 3rd params, `$tag` and `$priority, are required by `_wp_filter_build_unique_id()`
         * for historical reasons, when WP supported PHP versions where `spl_object_hash()` could
         * be unavailable. In modern WP, those params are there for backward compat, but not used.
         */
        $callbackId = _wp_filter_build_unique_id('', $callback, 0);

        $isInvokable and $callback = [$callback, '__invoke'];

        /** @var list{class-string|object, non-empty-string} $callback */

        return is_object($callback[0])
            ? [$callbackId, $callback[0], get_class($callback[0]), $callback[1]]
            : [$callbackId, null, $callback[0], $callback[1]];
    }

    /**
     * @param mixed $thing
     * @return bool
     *
     * @psalm-assert-if-true class-string|"object" $thing
     */
    private static function isClassLikeString($thing): bool
    {
        if (($thing === '') || !is_string($thing)) {
            return false;
        }

        if (($thing === 'object') || class_exists($thing) || interface_exists($thing)) {
            return true;
        }

        if (PHP_VERSION_ID < 801000) {
            return false;
        }

        // phpcs:disable PHPCompatibility.FunctionUse.NewFunctions.enum_existsFound
        return enum_exists($thing);
    }

    /**
     * @param mixed $targetObject
     * @param string $targetClass
     * @param bool $exactMatch
     * @return bool
     */
    private static function matchObjectClass(
        $targetObject,
        string $targetClass,
        bool $exactMatch = false
    ): bool {

        $isStringTarget = static::isClassLikeString($targetObject);

        if (
            (!$isStringTarget && !is_object($targetObject))
            || (($targetClass !== '@anonymous') && !static::isClassLikeString($targetClass))
        ) {
            return false;
        }

        /**
         * @var class-string|"object"|"stdClass" $objectClass
         * @var class-string|"object"|"@anonymous" $targetClass
         */

        $objectClass = $isStringTarget ? $targetObject : get_class($targetObject);

        if ($targetClass === 'object') {
            return !$exactMatch || ($objectClass === 'stdClass');
        }

        if ($targetClass === '@anonymous') {
            return strpos($objectClass, 'class@anonymous') === 0;
        }

        return $exactMatch
            ? ($targetClass === $objectClass)
            : is_a($objectClass, $targetClass, true);
    }

    /**
     * @param \Closure $closure
     * @param mixed $targetThis
     * @param array|null $targetArgs
     * @return bool True when there's a match.
     */
    private static function matchClosure(
        \Closure $closure,
        $targetThis = null,
        ?array $targetArgs = null
    ): bool {

        if (($targetThis === null) && ($targetArgs === null)) {
            return true;
        }

        // Ensures `$targetThis` is either `null`, `false`, an object, or a class/interface name
        $isObjectTarget = is_object($targetThis);
        $isClassTarget = !$isObjectTarget && static::isClassLikeString($targetThis);
        if (
            !$isObjectTarget
            && !$isClassTarget
            && ($targetThis !== null)
            && ($targetThis !== false)
        ) {
            return false;
        }

        $reflection = new \ReflectionFunction($closure);
        $closureThis = $reflection->getClosureThis();

        if (($targetThis === false) && ($closureThis !== null)) {
            return false;
        } elseif ($isObjectTarget && ($closureThis !== $targetThis)) {
            // If `$targetThis` was provided as object it must be same instance of closure's $this
            return false;
        } elseif ($isClassTarget && !static::matchObjectClass($closureThis, $targetThis)) {
            // If `$targetThis` was provided as class name in must match closure's $this
            return false;
        }

        // If `$targetThis` check above passed and there's no `$targetArgs`, it matched all
        if ($targetArgs === null) {
            return true;
        }

        $closureParams = $reflection->getParameters();

        if (($closureParams === []) || ($targetArgs === [])) {
            return $closureParams === $targetArgs;
        }

        return static::matchClosureParams($closureParams, $targetArgs);
    }

    /**
     * @param non-empty-array<int, \ReflectionParameter> $params
     * @param array $targetArgs
     * @return bool
     */
    private static function matchClosureParams(array $params, array $targetArgs): bool
    {
        [$ignoreType, $targetArgs] = static::normalizeTargetArgsList($targetArgs);

        if ($targetArgs === []) {
            return false;
        }

        if (count($params) !== count($targetArgs)) {
            return false;
        }

        $index = 0;
        foreach ($targetArgs as $targetArgName => $targetArgType) {
            $param = $params[$index];

            if ("\${$param->name}" !== $targetArgName) {
                return false;
            }

            if (!$ignoreType && !static::matchParamType($param, $targetArgType)) {
                return false;
            }

            $index++;
        }

        return true;
    }

    /**
     * @param array $array
     * @return list{bool, array<non-empty-string, non-empty-string>}
     *
     * phpcs:disable Inpsyde.CodeQuality.NestingLevel
     */
    private static function normalizeTargetArgsList(array $array): array
    {
        // phpcs:enable Inpsyde.CodeQuality.NestingLevel
        $mode = 'names';
        $i = -1;
        $normalized = [];
        foreach ($array as $key => $value) {
            $i++;
            $name = $value;
            if ($key !== $i) {
                if (($i > 0) && ($mode === 'names')) {
                    return [false, []];
                }
                $mode = 'types';
                $name = $key;
            }

            if (!is_string($key) && ($mode === 'types')) {
                return [false, []];
            }

            if (
                ($name === '')
                || ($name === '$')
                || !is_string($name)
                || (strpos($name, '$') !== 0)
            ) {
                return [false, []];
            }

            if ($mode === 'types') {
                ($value === null) and $value = 'mixed';
                if (($value === '') || !is_string($value)) {
                    return [false, []];
                }
            }

            /**
             * @var non-empty-string $name
             * @var non-empty-string $value
             */
            $normalized[$name] = ($mode === 'names') ? 'mixed' : $value;
        }

        return [$mode === 'names', $normalized];
    }

    /**
     * @param \ReflectionParameter $param
     * @param non-empty-string $targetArgType
     * @return bool
     */
    private static function matchParamType(\ReflectionParameter $param, string $targetArgType): bool
    {
        $paramType = $param->getType();

        if ($paramType === null) {
            // If declaration parameter has no type, given argument must be "mixed".
            return $targetArgType === 'mixed';
        }

        return (string) $paramType === ltrim($targetArgType, '\\');
    }
}
