---
name: plugin-upgrades
description: "How a change reaches the October CMS sites that already run Fortify. Use before changing a stored setting (its key, type or meaning), a DTO that is cached, updates/version.yaml, an update script in updates/, a default value, the contract the Fortify modules rely on (events, the Fortify settings model, DTOs, views, lang keys), or when preparing a release or a tag."
license: MIT
---

# Upgrading installed sites

Sites update the core and each module independently from the marketplace. Every change is written for a site that has been running the previous version for months.

## Versions and releases

- The marketplace gateway reads `updates/version.yaml` **from `main`**; Composer installs the latest stable **tag**. They must agree: the release workflow refuses a tag that is not the last version in `version.yaml`, or that is not on `main`.
- Semantic versions: a fix is a patch (`1.0.3`), a new option a minor, a removed option or a changed contract a major.
- Each version's note says what changes for the administrator, in one line.
- Release: merge the pull request, then `git tag -a v1.0.3 -m "..." && git push origin v1.0.3`. CI runs again on the tag and only then the `OCTOBER_MARKETPLACE_UPDATE_URL` hook asks the marketplace to build it.

## Stored settings

The settings live in one `system_settings` row (`item = wobqqq_fortify_fortify`, JSON in `value`) that the modules share.

- Changing a setting's type, key or meaning ships an update script in `updates/` listed under its version in `version.yaml`:
  - it reads and writes `system_settings` through `DB` (not the model: the model boots every module's listeners and validation during the update);
  - it converts every row with that `item` (one per site in a multisite install) and skips rows without the setting or with invalid JSON;
  - it keeps the site's current behaviour, unless the change is the fix itself, and says so in the version note;
  - `down()` converts back;
  - it is covered by a test against a `system_settings` table.
- **Prefer a new key to converting an ambiguous value.** When the old value cannot be told apart from a valid new one (a switch stored as `1` is also "1 day"), store the new setting under a new key and ignore the old one, as `password_policy_expire_after_days` replaced the `password_policy_expire_days` switch. A conversion script is for values it can convert without guessing.
- An update script that writes `system_settings` directly bypasses October's query cache (`SettingModel::getSettingsRecord()` remembers the record for a day) and the plugins' caches: clear them, or avoid the script.
- The transformer still accepts the old shape: a site can run the new code before its update script ran.
- Never rename a key another module reads (`config`, `tests`, `ip_firewall`, `csp`, `input_sanitizer`).

## Cached objects

- `Cache::remember` serializes DTOs. A DTO whose properties change type or order must not be read back from the previous version's cache: bump `BasicCache::VERSION` (the modules inherit it) or give the cache its own key suffix.
- `BasicCache::remember()` already treats an unreadable cache entry as a miss; keep reading through it.

## The modules' contract

A site may run a new core with old modules or new modules with an old core.

- Only add to the contract listed in AGENTS.md. A module that needs a new core API checks for it (`method_exists`, `enum_exists`, `defined`) and falls back.
- New events and new enum cases are fine; changing the arguments an event passes by reference is not.
- Run the modules' test suites against the changed core before releasing it.

## Defaults

- A new protection ships disabled, or with a default that cannot block the current administrator.
- A default that changes for existing sites is an update script, not a changed `default:` in YAML (the YAML default only applies to an empty field).
