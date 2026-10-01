# Input Sanitizer

[![CI](https://github.com/wobqqq/oc-fortify-input-sanitizer-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/oc-fortify-input-sanitizer-plugin/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/wobqqq/fortifyinputsanitizer-plugin)](https://packagist.org/packages/wobqqq/fortifyinputsanitizer-plugin)
[![Downloads](https://img.shields.io/packagist/dt/wobqqq/fortifyinputsanitizer-plugin)](https://packagist.org/packages/wobqqq/fortifyinputsanitizer-plugin)
[![Marketplace](https://img.shields.io/badge/October%20CMS-Marketplace-e24848)](https://octobercms.com/plugin/wobqqq-fortifyinputsanitizer)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://github.com/wobqqq/oc-fortify-input-sanitizer-plugin/blob/main/composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/wobqqq/oc-fortify-input-sanitizer-plugin/blob/main/phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/wobqqq/oc-fortify-input-sanitizer-plugin/blob/main/LICENSE.md)

**Input Sanitizer** protects your application by blocking malicious input data in requests.

Fully integrated with the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) security system, it adds an extra layer of defense against XSS and injection attacks.

## 📊 Security Dashboard Widget

Fortify includes a dashboard widget that gives you an overview of your application’s security status.

- Highlights critical vulnerabilities and misconfigurations
- Provides quick access to all security checks and tools
- Helps you identify and fix issues in one place

This widget acts as a central hub, allowing you to monitor and manage your application's security at a glance.

## 🚀 Features

- Detects and blocks malicious payloads (the request is refused, its input is never rewritten)
- Scans the query string, form input, headers and URL segments
- Protects against XSS and injection attacks
- Lightweight and efficient filtering

## 🔗 Related Plugins

- [Fortify](https://octobercms.com/plugin/wobqqq-fortify) – comprehensive security suite
- [Admin IP Access](https://octobercms.com/plugin/wobqqq-fortifyadminipaccess) – restrict admin panel access by IP
- [IP Blocker](https://octobercms.com/plugin/wobqqq-fortifyipblocker) – manually block specific IP addresses
- [Smart IP Blocker](https://octobercms.com/plugin/wobqqq-fortifysmartipblocker) – automatic IP blocking based on request rate
- [CSP](https://octobercms.com/plugin/wobqqq-fortifycsp) – add Content Security Policy headers to prevent XSS

## 📦 Requirements

- PHP 8.2 or higher
- October CMS 3.x or 4.x
- [Fortify](https://octobercms.com/plugin/wobqqq-fortify)

## 📥 Installation

| From | How |
|---|---|
| **October CMS Marketplace** | [octobercms.com/plugin/wobqqq-fortifyinputsanitizer](https://octobercms.com/plugin/wobqqq-fortifyinputsanitizer), or **Settings → Updates & Plugins → Install plugins** in the backend and search for “Fortify Input Sanitizer” |
| **Artisan** | `php artisan plugin:install Wobqqq.FortifyInputSanitizer` |
| **Composer** | `composer require wobqqq/fortifyinputsanitizer-plugin` then `php artisan october:migrate` |

It needs the [Fortify](https://octobercms.com/plugin/wobqqq-fortify) core plugin: Composer installs it with the module; when installing from the marketplace, install **Fortify** first.

## 💻 Usage

All configuration and management is handled via the October CMS admin panel.

**Admin Panel:**
Navigate to `Settings -> Fortify` and enable **Input Sanitizer**.

**Console Commands:**

- Disable Input Sanitizer:
```bash
php artisan wobqqq.fortify:input-sanitizer:disable
```

## ⬆️ Upgrading

- **1.0.4** — installing the module with Composer installs the Fortify core with it. Nothing changes on an existing site.
- **1.0.3** — a pattern that does not compile is refused when the settings are saved, and one already saved is skipped instead of failing every page of the site. A pattern that gives up on a long input (backtracking limit) lets the request through instead of breaking it. The settings are validated on every save and applied as soon as they are saved.

## ⚠️ Good to know

- The sanitizer is defence in depth: it stops common payloads, not every attack. Keep validating input and escaping output in your own code.
- The query string and form input are scanned, not JSON request bodies or uploaded files.
- Rich-text editors send HTML on purpose: add their input names to *Excluded inputs*, or every save is refused.

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](https://github.com/wobqqq/oc-fortify-input-sanitizer-plugin/blob/main/SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. The module is tested together with the [Fortify core](https://github.com/wobqqq/oc-fortify-plugin), which Composer installs from Packagist.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, YAML lint, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
```

Every pull request runs the same checks on GitHub Actions, plus a syntax check on PHP 8.2 and a run against the latest core. Pushing a tag that matches the last version in `updates/version.yaml` publishes it as a GitHub release and to the October CMS marketplace once CI has passed.

