---
name: dependency-injection
description: "How an October plugin's classes get their collaborators and configuration. Use when writing a constructor in services/, client/, queries/, listeners/ or cache/, calling a facade (Db, Cache, Http, Mail, Event, Config, BackendAuth, Flash) or app() outside an entry point, binding something in Plugin::register(), reading the plugin's SettingModel or config, reaching for inheritance, a Base* class or a trait to share behaviour, writing a static method, or when a class is hard to test."
license: MIT
---

# Dependency injection in an October plugin

October leans on facades and static helpers; the plugin's own classes do not have to. The constructor is the contract: what a class needs is listed there.

## 1. Where facades are fine

- Entry points October constructs (components, backend controllers, widgets, Plugin.php, console command `handle()`): facades, `app()`, `post()`, `Flash`, `Backend::url()` are fine.
- Everything under `services/`, `client/`, `queries/`, `listeners/`, `cache/` takes its collaborators through the constructor (promoted `private readonly`), so tests can pass fakes and a hidden real call cannot slip through.

| Facade | Inject |
|---|---|
| `Db` | `Illuminate\Database\ConnectionInterface` |
| `Event` | `Illuminate\Contracts\Events\Dispatcher` |
| `Cache` | `Illuminate\Contracts\Cache\Repository` |
| `Http` | `Illuminate\Http\Client\Factory` |
| `Mail` | `Illuminate\Contracts\Mail\Mailer` |
| `Config` / `Settings::get()` | a typed DTO built by a transformer (see §3) |
| `BackendAuth::getUser()` | the acting user/id passed in the DTO by the entry point |
| `now()` in logic | `Psr\Clock\ClockInterface` |

## 2. Classes first, interfaces at boundaries

- Depend on a concrete `final` class for pure logic. Introduce an interface where there is I/O or real variation (network clients, probes, storage, strategies) or where other plugins must plug in.
- Name interfaces after the concept, implementations after what differs (`Probe` → `TcpProbe`, `TlsProbe`); no `*Interface` suffix.
- Bind in `Plugin::register()` (`$this->app->bind(Probe::class, TcpProbe::class)`), so another plugin or a test can rebind.

## 3. Settings and config arrive typed

- The plugin's `SettingModel` and config are read in one place — a transformer/instance class that returns a `final readonly` DTO with validated, cast values — and that DTO is injected or passed into services. Services never call `Settings::get('some.key')`.
- Cache the DTO (not the model) and clear it on the model's `eloquent.saved`/`eloquent.deleted` events.
- Options that never vary are typed class constants (`private const int MAX_TARGETS = 20;`).

## 4. Composition over inheritance

- Every non-abstract class is `final`. October's own base classes (`PluginBase`, `ComponentBase`, `Model`, `SettingModel`, `ReportWidgetBase`, `Controller`) are the only parents.
- Shared behaviour is an injected helper or a decorator implementing the same interface, not a `Base*` class or a trait. Traits only where October requires them (`Validation`, `SoftDelete`, `Sortable`).
- Extend other plugins' and core models through `Model::extend()`/events from a listener class, never by subclassing them.

## 5. Statics

- Static methods only for pure helpers without I/O (cache key builders, formatters, named constructors). Anything that reads settings, the database, the network or the clock is an instance method of an injected class.
- No mutable static state; a per-request memo belongs in a scoped/singleton service the container owns.

## Checklist

- [ ] Facades only in entry points; services list their collaborators in the constructor.
- [ ] Interfaces at I/O boundaries and extension points, bound in `Plugin::register()`.
- [ ] Settings reach services as typed DTOs from one transformer.
- [ ] Classes `final`; no Base* classes or logic traits.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
