---
name: plugin-boundaries
description: "Rules for an October plugin that other plugins depend on or that sites upgrade in place. Use when changing a class, event name or argument, settings key, view name, language key, permission code, component alias or database table another plugin or a site may use, when adding an update script to updates/version.yaml, when the plugin needs something from another plugin, or when deciding what is public API versus internal."
license: MIT
---

# Plugin boundaries

A plugin runs on sites you do not control and is upgraded independently of its sibling plugins. The architecture skills apply; these rules come on top. Upgrade mechanics are in the `plugin-upgrades` skill.

## 1. Public API is a promise

- Public: classes other plugins call or extend through events, event names and the arguments they pass by reference, settings keys, view and language keys, permission codes, component aliases, tables and stored formats. Everything else is `@internal`.
- Add; do not rename or remove. A module must keep working with every released core version it allows in `composer.json`.
- Extension points are events (named in an enum) and interfaces bound in `Plugin::register()`, never inheritance of plugin classes.

## 2. No assumptions about the site

- Read site-specific values from the plugin's settings or config with safe defaults; never hard-code hosts, paths, backend URIs or user ids.
- New protections ship disabled or in a mode that cannot lock an administrator out, with a console command to recover.
- Check that optional plugins are present and enabled before using them.

## 3. Data that already exists

- Every shipped change adds a version to `updates/version.yaml`; a released update script is never edited.
- A change to what is stored comes with an update script that converts existing rows and a working `down()`, or uses a new key and leaves old data readable.
- Settings are read tolerantly: unknown keys ignored, missing or invalid ones defaulted by the transformer.

## Checklist

- [ ] Public vs `@internal` explicit; no renames or removals of public API.
- [ ] Site specifics from settings/config; safe defaults; a recovery path.
- [ ] `version.yaml` entry for every change; stored data stays readable after upgrade.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
