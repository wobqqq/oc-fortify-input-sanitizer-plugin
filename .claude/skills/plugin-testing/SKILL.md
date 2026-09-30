---
name: plugin-testing
description: "How this module is tested without a licensed October CMS install. Use when writing or changing a test, adding a class October provides to tests/Stubs/October.php, or when a test fails only in the suite (leaking statics between tests)."
license: MIT
---

# Testing the module

## The harness

- Pest 4 on Orchestra Testbench 10 (Laravel 12), the real `october/rain` and the Fortify core from Packagist (`vendor/wobqqq/fortify-plugin`, loaded through the classmap in `composer.json`).
- October's modules are licensed and not installable in CI. `tests/Stubs/October.php` stands in for the classes the plugins touch, with October 4.4's signatures and behaviour: `PluginBase`, `PluginManager`, `SettingModel` (in-memory record, real Eloquent and October model events), `Backend` and `BackendAuth`, the `Form` widget, `Settings`. It is the same file as the core's; change both together.
- `tests/TestCase.php::bootPlugins()` registers and boots the core, then the module, as October does on every request, after resetting the per-request state (the settings instance, the DTO singletons, the "middleware added" flags). Call it again after changing the settings to act as the next request. `tearDown()` clears October's statics (extensions, model event listeners).

## Rules

- Test what a visitor or an administrator sees: the response a blocked request gets, the middleware groups October ends up with, the settings form, the validation of a setting, the console commands.
- A security rule is a test: the lock-out protection, a spoofable input, an invalid setting, a cache that must be cleared when the settings change.
- Time goes through `Carbon::setTestNow()`; no test sleeps or reaches the network.
- Coverage stays at 90 % or more (`make test.coverage`).
