---
name: fortify-security
description: "Security checklist for the Fortify suite. Use for any change to what an outside value can do or who may reach something: a backend AJAX handler (on*() in widgets/ or a form), a middleware, a partial or view that prints a value, a setting and its validation rule, a scanner that opens a network connection (client/), the config the plugin overrides (session, password policy, backend), a console command, the IP, CSP or input rules of a module, caching of security decisions, or a security review of this plugin."
license: MIT
---

# Fortify security checklist

Fortify runs on production sites and decides who gets in. Treat every rule below as a test to write, not a guideline to remember.

## 1. Output: escape everything

- Every value printed in a partial or view goes through `e()`: URLs, IPs, hosts, ports, statuses, icons, classes, `href`, `target`.
- `data-request-data` is built with `e(json_encode([...], JSON_THROW_ON_ERROR))`, never by concatenating values into a JS object literal.
- Language strings may hold markup (`<b>`), so `trans()` output is printed raw; **the replacements are escaped** before they go in: `Lang::get('...', ['uri' => e($uri)])`.
- An exception message shown to an administrator is escaped (`_error.htm` does `e($error)`).
- `target="_blank"` always carries `rel="noopener noreferrer"`.
- Test it: store a value such as `"'});alert(1);//` or `<script>` and assert the rendered HTML does not contain it raw (see `tests/Feature/WidgetTest.php`).

## 2. Access: authorize every entry point

- A backend AJAX handler checks the permission itself: `BackendAuth::userHasAccess(Permission::FORTIFY->value)`. The widget being hidden from a role does not stop a direct POST.
- A handler dispatches a fixed list (`match (ButtonAction::tryFrom(...))`). Never call a method, class or view whose name comes from the request.
- New permissions are an `enums/Permission` case **and** an entry in `plugin.yaml`.
- Console commands are the recovery path when an administrator is locked out (`*:disable`, `add-ip`); keep one for every protection that can lock someone out.

## 3. Input: validate settings twice

- `Fortify::$rules` (and the rules each module adds in its listener) bound every field: `integer|min|max`, `ip`, `url:http,https`, `max:` lengths, `array|max:` counts, a strict `regex` for lists.
- The transformer normalizes again (`trim`, type checks, ranges): the stored record may predate the rules or be written by `Fortify::set()`.
- A regular expression an administrator enters is compiled only after it was validated; a broken one must never turn every request into a 500.

## 4. Scanners (`client/`): no SSRF, no hangs

- A scan only targets what the settings list (`SensitiveFileCheckerService::check()` refuses any other URL; the TCP and TLS checks look the target up in the configured list).
- Every connection has a connect and a total timeout; the TCP check dials all ports at once and waits one timeout in total.
- HTTP requests do not follow redirects (a redirect to the home page is not an exposed file), send no cookies or credentials, and the response body is never stored or echoed.
- IPv6 literals are bracketed (`tcp://[::1]:22`).

## 5. Config hardening

- Only write the keys October reads (`backend.password_policy.require_nonalpha`, not `require_non_alpha`; `expire_days` is a number of days or `false`). Check the key in October's `modules/backend` before adding one.
- The override runs once per request (`ConfigService::$overrideConfig`) and only while `config.enabled` is on.
- A new hardening default must not lock the current administrator out (the IP modules add the current IP to their lists before saving and refuse a list without it).

## 6. Caching security decisions

- A cached DTO decides who is blocked: it is cleared on every settings save and delete (`model.afterSave` / `model.afterDelete`).
- Cache keys are namespaced (`BasicCache::cacheKey()` hashes the class and `VERSION`); never use a bare key such as `ban:1.2.3.4` that another package could write.
- A cached object whose class changed shape must not crash the site: `BasicCache::remember()` falls back to a fresh value, and `VERSION` is bumped.

## 7. Secrets and data

- Never print, log or send `.env`, `auth.json`, `system_parameters`, cookies or `Authorization` headers.
- The scanners send a neutral `User-Agent` and nothing identifying the site's administrators.

## Review procedure

1. `git diff --stat` and list every changed handler, partial, setting, rule, client and cache.
2. Walk each through sections 1 to 6 and name the test that pins it.
3. Run `make ready`.
4. Report each finding as: file:line, what an attacker sends, what happens, the fix.
