---
name: domain-layer-cqrs
description: "When an October plugin's rules deserve their own objects, and how to keep reads apart from writes. Use when the same state rule repeats across services, components and handlers, when a model needs invariants over its related records, when considering entities with behaviour, value objects, query classes, cached read DTOs (instances/, cache/), reports on the dashboard, or event sourcing — and before introducing any of these, to check the plugin needs them."
license: MIT
---

# Domain objects and read/write separation in an October plugin

Most plugins are settings, a few models and integrations: services over Eloquent are the right size. Reach for the patterns below only for a concrete pain.

| Pattern | Introduce only when | Not when |
|---|---|---|
| Behaviour on models | a state rule repeats in several services/handlers | the model is a form-to-table mapping |
| Plain PHP domain objects | the rules are the plugin's core (scoring, matching, policies) and need exhaustive unit tests | CRUD and settings |
| Query classes / read DTOs | lists, reports and widgets need joins, aggregates or caching | `Model::orderBy()->paginate()` |
| Event sourcing | never in a plugin unless history is the product; an audit table covers "we want history" | — |

## 1. Rules live with the data

- Put state rules in action-named model methods (`$entry->expire()`, `$ban->lift()`), not in each handler.
- A parent that owns children (a policy and its directives, a list and its entries) changes them through its own methods, which check the shared rule once.
- Pure rule engines (pattern matching, scoring, CIDR matching) are `final` classes with value objects, independent of October, and unit tested.

## 2. Reads apart from writes

- Writes go through services that load from the database and never from a cache.
- Reads for widgets, lists and reports go through `queries/` classes that return DTOs, cached where it pays (`cache/` + `instances/` per-request memo), and are invalidated by model or plugin events.
- A cached value is a DTO with a versioned cache key; changing its shape bumps the version so an old entry is never unserialized into the new class.

## Checklist

- [ ] The pattern answers a present pain.
- [ ] State rules are model/domain methods, not repeated in handlers.
- [ ] Reads via query classes returning DTOs; writes never read from caches.
- [ ] Cached DTOs use versioned keys and are cleared on change.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
