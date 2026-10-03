---
name: application-layer
description: "Where an October CMS plugin's business logic lives and how entry points hand work to it. Use when adding or changing an action of the plugin (save, scan, block, import, send…), when Plugin.php, a CMS component (onRun, onSomething AJAX handlers), a backend controller or its AJAX handlers, a report widget handler, a console command, routes.php, a queued job or an event listener grows beyond input → call → response, when two entry points need the same behaviour, when writing a class under services/ or dto/, or when a method takes post(), input() arrays, a model's attributes array or a boolean flag that switches behaviour."
license: MIT
---

# The application layer in an October plugin

Plugin.php, components, backend controllers, widgets, console commands and `routes.php` are **entry points**. They read their own input, call one service, and turn the result into their own output (partial, flash, redirect, JSON, console line). The work in between lives in the plugin's own classes and does not know who called it.

Plain backend CRUD built with `FormController`/`ListController` and model validation needs no service. Add one when an action has rules beyond field validation, or more than one entry point uses it.

## 1. Layout

Keep the layout the plugin already uses (lowercase folders, PascalCase namespaces):

| Folder | Holds |
|---|---|
| `Plugin.php` | registration only: components, navigation, permissions, settings, widgets, console commands, event subscribers, `boot()` wiring. No logic. |
| `services/` | one class per use case or small cohesive group, `final`, constructor-injected |
| `dto/` | `final readonly` input/output objects |
| `enums/` | every shared code: events, permissions, views, statuses |
| `transformers/` | the edge that turns untrusted settings/input/model data into DTOs |
| `queries/` | read-side queries returning DTOs or models for lists and reports |
| `listeners/` | event subscribers: thin, call a service |
| `client/` | the only code that talks to the network |
| `models/` | Eloquent models, relations, validation rules, small state methods |

## 2. Entry points stay thin

```php
// ✅ backend controller AJAX handler
public function onScan(): array
{
    $dto = ScanTransformer::fromPost(post());
    $report = app(SiteScanner::class)->scan($dto);

    Flash::success(__('wobqqq.fortify::lang.scan.done'));

    return ['#scan-report' => $this->makePartial('report', ['report' => $report])];
}
```

- Components and backend controllers are built by October, so constructor injection is not available there: resolve the service with `app(Service::class)` **in the handler** (the entry point). Services themselves receive everything through their constructor.
- A component's `onRun()` prepares page variables by calling query/service classes; it does not run queries inline.
- `Plugin::boot()` registers; if something must run at boot (config override), it calls one service method.

## 3. Input: typed DTOs, never post() arrays

- A service receives DTOs (or ids/value objects), never `post()`, `input()`, `Request`, or a model's `getAttributes()` array.
- Build the DTO in one place (a transformer or a static `fromPost()`), casting and normalising each field explicitly — settings and form data are untrusted strings.
- Assign model attributes explicitly in the service (`$entry->ip = $dto->ip->value;`), never `fill(post())`.
- A boolean that switches behaviour (`scan($targets, bool $quick)`) hides two use cases: make two methods/classes or an injected strategy.

## 4. Data access and transactions

- The service loads what it changes, by id, and writes inside `Db::transaction()` (or an injected `ConnectionInterface`) when several rows must stay consistent.
- Slow work (network probes, file reads) runs before the transaction; mail, cache clearing and events after it (see `events`).

## 5. Output

- Return a DTO, an id or `void`. Failures are exceptions (see `error-handling`), never `false`/`null`.
- Partials receive DTOs and escape everything they print (`e()`); they do not call services.

## Checklist

- [ ] Plugin.php only registers; handlers build a DTO, call one service, render.
- [ ] Services are `final`, constructor-injected, take DTOs/ids, return values or `void`.
- [ ] No `fill(post())`, no behaviour-switching booleans.
- [ ] Multi-row writes in one short transaction; side effects after commit.
- [ ] October API details: the `octobercms-*` skills.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
