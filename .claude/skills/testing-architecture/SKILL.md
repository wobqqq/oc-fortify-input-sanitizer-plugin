---
name: testing-architecture
description: "What to test at which level in an October plugin and what to fake. Use when deciding between a unit test and a feature test, testing a service, transformer, value object, listener, component handler, backend controller handler, widget or console command, when a test needs an October class missing from tests/Stubs, when reaching for mocks or facade fakes, or when a class is hard to test. Pest syntax and the stub conventions are in the plugin-testing / testing-best-practices skills."
license: MIT
---

# Testing architecture in an October plugin

The plugins are tested with Pest on Orchestra Testbench and the real `october/rain`; the licensed October modules (system, backend, cms) are replaced by stubs in `tests/Stubs/` that copy October's signatures and the behaviour the plugin relies on.

## 1. Level by kind of code

| Code | Test |
|---|---|
| Value objects, transformers' parsing, pure helpers, calculations | unit: plain PHPUnit/Pest, no container, no database |
| Services that use models, settings, cache, events | feature: Testbench app, SQLite/test DB, real container |
| Components, controller/widget AJAX handlers, console commands, Plugin registration | feature: call the handler or command and assert output, flash, partial data, exit code |
| Network clients | feature with the transport faked (`Http::fake()`, local sockets in `tests/Support`) — never the real internet |

- Do not mock models or the settings model to unit test a service; test it as a feature test. If its rules need many edge cases, move the rules into a value object or a pure class and unit test that.
- Each bug fix starts with a failing test.

## 2. Doubles only at the edges

- Fake what leaves the process: HTTP, sockets, mail, queue, clock. Use the real DTOs, value objects and transformers.
- Prefer framework fakes and small hand-written fakes implementing the plugin's interfaces over `expects()->once()` mocks; assert the outcome (what was stored, rendered, dispatched).
- Block stray network calls in the test base.

## 3. Stubs follow October

- When the plugin starts using another October class or method, add it to `tests/Stubs/October.php` with October's real signature and only the behaviour the plugin depends on. A stub that behaves differently from October makes tests lie; verify against a real October install (the sandbox) when in doubt.

## 4. Assert behaviour, not storage

- Check results through the plugin's own read paths (settings DTO, query class, rendered partial) rather than raw table rows, unless persistence is the requirement (migrations, stored format compatibility).
- Cover authorization: handlers refuse users without the plugin's permission.
- Cover upgrades: a test that loads settings saved by the previous version still yields a valid DTO.

## 5. Architecture tests

Guard `declare(strict_types=1)`, `final` classes, no debug helpers, and layering (services do not use `post()`/`Request`; only `client/` opens network connections) with Pest `arch()` tests.

## Checklist

- [ ] Pure logic unit tested on edges; services and handlers feature tested.
- [ ] Doubles only at process boundaries; no real network.
- [ ] Stubs match October's real signatures.
- [ ] Authorization and upgrade compatibility covered.
- [ ] Architecture tests guard types, finality and layering.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
