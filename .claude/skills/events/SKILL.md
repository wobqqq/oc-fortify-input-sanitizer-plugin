---
name: events
description: "Reactions and side effects in an October plugin. Use when subscribing with Event::listen/Event::subscribe, extending another plugin or core model (Model::extend, bindEvent, model.afterSave, eloquent.saved: Class), hooking backend events (backend.form.extendFields, backend.menu.extendItems, backend.page.beforeDisplay), defining the plugin's own events for other plugins (an enum of event names, arguments passed by reference), clearing caches when settings change, queueing jobs, or sending mail after an action."
license: MIT
---

# Events in an October plugin

October is extended through events, so plugins use them in two roles: **integration** (hooking into core and other plugins) and **reaction** (side effects after the plugin's own actions). Keep both explicit and thin.

## 1. Subscribers are wiring

- Register listeners in `Plugin::boot()` through subscriber classes in `listeners/` (`Event::subscribe(FortifyListener::class)`), one class per area.
- A listener method extracts what it needs and calls a service; it holds no business rules.
- Name the plugin's own events in an enum (`FortifyEvent::WIDGET_ITEMS`), never as string literals scattered through the code. Their names and argument shapes are public API for other plugins (see `plugin-boundaries`).

## 2. Model events: pick the stable hook

- `bindEvent()` on a model instance only affects instances created after the binding; a settings instance created earlier never sees it. To react to every save of a model class, listen to `eloquent.saved: ` . `Model::class` / `eloquent.deleted: ` . `Model::class`.
- Model events fire per row, inside transactions, and not for mass updates. Use them for technical reactions to that row: clear the cached settings DTO, bump a cache version, refresh a derived file.
- Business reactions are explicit: the service that performs the action dispatches a past-tense event (`IpBlocked`, `ScanCompleted`) with ids and values, not models.

## 3. After the commit, in the background

- Mail, notifications, HTTP calls, cache warming and heavy work run after the transaction commits (`Db::afterCommit()`, `ShouldDispatchAfterCommit`, `afterCommit()` on jobs and mailables), preferably queued.
- Queued jobs are idempotent (they can run twice), have timeouts and retries, and load fresh data by id.
- Do not run slow work in `Plugin::boot()` or in a listener on every request (`backend.page.beforeDisplay`): cache what a hook needs and invalidate it on change.

## 4. Extending other code

- Extend core and other plugins' models and forms from a listener class with `Model::extend()` and `backend.form.extendFields`; check the form's model and context before adding fields.
- Guard against the other plugin being absent or disabled (`PluginManager::instance()->hasPlugin()` / `isDisabled()`).
- Never subclass another plugin's classes to change behaviour.

## Checklist

- [ ] Subscribers registered in `Plugin::boot()`, listeners thin, events named in an enum.
- [ ] Every-save reactions use `eloquent.saved: Class`, not `bindEvent` on a possibly earlier instance.
- [ ] Business reactions are explicit events from services with id payloads.
- [ ] Side effects after commit and queued; jobs idempotent.
- [ ] Extensions check the target's presence and context.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
