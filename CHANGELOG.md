# Object Hooks Remover

## Next

- Modernize codebase to support recent PHP versions and up-to-date Syde coding standards.
- Move logic from `utils.php` file to an internal `Functions` class (all-static, only for encapsulation and autoload).
- Modernize QA:
    - Move out from Travis to GitHub actions.
    - Rewrite tests, update PHPUnit version, tests now include the real WordPress functions instead of stubs.
    - Added static analysis.
- Introduced `remove_all_object_hooks()`.
- Introduced `remove_static_method_hook()` to replace the now deprecated `remove_class_hook()` (which is converted to an alias).
- In `remove_closure_hook()` is now possible to use `"mixed"` as target parameter type when the closure param declare no type.
- License change from MIT to GPL due to usage of WordPress functions.
- README refresh.

---

## v0.1.1 (2017-12-19)

### Fixed

- Fix PHP 7 compatibility when removing hook with closures declaring param types.

---

## v0.1.0 (2017-12-03)

First release.