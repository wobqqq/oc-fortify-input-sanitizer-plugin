# AGENTS.md

Guidance for AI coding agents (Claude Code, Codex, Junie, Cursor) working in this repository.

## What this is

**Input Sanitizer** (`Wobqqq.FortifyInputSanitizer`) is a free module of the Fortify security suite for October CMS 3.x/4.x (built and tested against 4.4 on Laravel 12, PHP 8.2+). It scores every front-end request — the query string, the form input (nested arrays included), the headers and the URL segments, each decoded up to three times — against the administrator's regular expressions for XSS, encoded XSS, command injection, path traversal, template injection, null bytes and CSV injection, and answers 400 with the page the administrator chose once the score reaches the threshold.

It requires the core plugin [`Wobqqq.Fortify`](https://github.com/wobqqq/oc-fortify-plugin): the settings live in the core's `Wobqqq\Fortify\Models\Fortify` record under the `input_sanitizer` key and appear on **Settings → Fortify**, and the module draws its own item on the core's dashboard widget.

This is a **security product installed on production sites**. A bug here locks administrators or visitors out, or silently leaves a site unprotected. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP.

```bash
make install        # composer install inside the php container (the core comes from Packagist, as `wobqqq/fortify-plugin`)
make code.fix       # composer normalize, rector, php-cs-fixer
make code.check     # validate, normalize --dry-run, audit, php -l, yaml-lint, cs, rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, fails below 90 %
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories reported by `composer audit` are fixed by updating the package, never ignored.

## How the code is laid out

| Path | Holds |
|------|-------|
| `Plugin.php` | Wiring: the console command, the `input_sanitizer_regex` validation rule, the listener, the middleware. |
| `services/InputSanitizerService.php` | The scan: decoding, scoring across the whole request, exclusions, the middleware registration. |
| `services/PatternMatcher.php` | The only place a pattern is run. A pattern that does not compile or gives up is no match, never an error. |
| `http/middlewares/InputSanitizerMiddleware.php` | Runs the scan on every request of `cms.middleware_group` (the site, not the backend). |
| `transformers/FortifyTransformer.php` | Turns the stored settings into `InputSanitizerDto`, keeping only the patterns that compile. |
| `cache/, instances/` | The cached DTO (cleared on every settings save) and its per-request memo. |
| `listeners/FortifyListener.php` | Wires the events to `SettingsService` and clears the module's cache when the settings are saved or deleted. |
| `services/SettingsService.php` | The settings form fields, their validation rules, the default patterns, the dashboard item. |
| `validator/rules/InputSanitizerRegexRule.php` | Refuses a pattern that does not compile when the settings are saved. |
| `console/` | `disable`, the recovery path. |
| `updates/version.yaml` | The version history the marketplace reads from `main`. |

### Working with the core

- The core is a separate plugin that sites update on their own schedule. Use only the core's public API (listed in the core's AGENTS.md: the `Fortify` settings model, `FortifyEvent`, `View`, `WidgetItemColor`, the widget DTOs and `FortifyTransformer::widget*Dto()`, `BasicCache::cacheKey()`/`TTL`). A new core API is used only behind a check (`method_exists`, `enum_exists`) with a fallback, so the module keeps working on every released core.
- Settings are validated by rules the module adds to the core model. Add them in `Fortify::extend()` **and** when the settings form is built: the settings instance may exist before the module extends the model.
- Caches are cleared on the `eloquent.saved` / `eloquent.deleted` events of the core model, never with `bindEvent()` on an instance, for the same reason.

## Architecture

Read the architecture skills before changing how the module is structured: `application-layer`, `dependency-injection`, `error-handling`, `validation`, `events`, `testing-architecture`, `domain-layer-cqrs` and `plugin-boundaries`. The listener only wires events: the settings section (defaults, rules, fields, the dashboard line) lives in `services/SettingsService.php`.

## Upgrading installed sites safely

Read the `plugin-upgrades` skill before changing anything that reaches a site that already runs the module: a new version in `updates/version.yaml` for every shipped change, an update script for every change to what is stored, a new cache key for every change to a cached object's shape, and defaults that cannot lock anyone out.

## Security rules (always)

Read the `fortify-security` skill for the full checklist. For this module in particular:

- A pattern is run only through `PatternMatcher` (an arch test enforces it): an administrator's broken or catastrophic pattern must never turn into a 500 or a blocked site.
- The filter is defence in depth. It never replaces validation and output escaping in the site's own code; do not describe it as making a site safe.
- Only the query string and form input are scanned, not JSON bodies or uploaded files. Say so wherever the coverage is described.
- Every front-end request runs every pattern over every value: keep the decoding bounded (three rounds) and do not add work that grows with the size of the request beyond one pass.
- Cookies and `Accept` are never scanned (their values are not typed by the visitor and hold characters the patterns flag).

Recovery from the console, for an administrator who locked themselves out:

- `php artisan wobqqq.fortify:input-sanitizer:disable` — turns the module off, for a site blocked by a pattern that is too broad.

## Tests

Pest 4 on Orchestra Testbench (Laravel 12) with the real `october/rain` and the core plugin from Composer. The licensed October modules are not installable in CI, so `tests/Stubs/October.php` reproduces the classes the plugins touch with October 4.4's behaviour (keep it identical to the core's copy). `tests/TestCase.php` boots the core and the module like October does. Read the `plugin-testing` skill.

## Git workflow

- `main` is protected: **never push to it and never force-push.** Every change goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for sites that upgrade);
  4. merge only once CI is green, then delete the branch.
- A release is a tag pushed on a merged commit of `main` (the `plugin-upgrades` skill says how); the tag is the only thing pushed outside a pull request.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via php-cs-fixer (`(int)$x` without a space, imported classes).
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- DTOs are `final readonly`; services and transformers are `final`.
- October patterns over Laravel ones: model validation, form fields added in `backend.form.extendFields`, `Plugin.php` registration, the core's `lang` keys (read the `octobercms-*` skills).
- Commits: imperative subject saying what the change does for the site, a body with the why.
