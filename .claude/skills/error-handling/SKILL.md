---
name: error-handling
description: "How failures travel in an October plugin from where they are detected to the backend user, the visitor, the console and the log. Use when a service could fail (not found, not allowed, limit reached, remote host unreachable), when tempted to return false/null or a status array, when creating an exception class, throwing ApplicationException, ValidationException or SystemException, writing try/catch in a component, backend AJAX handler, widget or console command, showing Flash messages, or deciding what goes to the log."
license: MIT
---

# Error handling in an October plugin

A service has one successful path; every deviation is an exception whose type says who should react. Return values describe success only.

## 1. October's exception types, used deliberately

| Situation | Throw | Shown as |
|---|---|---|
| A field is invalid | `ValidationException` (model `Validation` trait, or `new ValidationException(['field' => $message])`) | field error in the form / AJAX response |
| The action cannot happen for a business reason | the plugin's `BusinessException` (extends `ApplicationException`) | the message, flashed by the AJAX framework |
| A programmer error or broken environment | let it propagate, or `SystemException` with technical detail | generic error; logged |

```php
abstract class BusinessException extends \October\Rain\Exception\ApplicationException {}

final class TargetLimitReached extends BusinessException
{
    public function __construct(int $limit)
    {
        parent::__construct(__('wobqqq.fortify::lang.errors.target_limit', ['limit' => $limit]));
    }
}
```

- One small class per reason, named as a fact; the message is translated and safe to show (no paths, hosts, SQL).
- `ApplicationException` messages reach the user verbatim: never put an exception message from a lower layer into one.

## 2. No failure-by-return-value

- No `bool $ok`, `null`-for-error, `['success' => false]` from services. Use `findOrFail()`, `saveOrFail()`.
- `?Type` is fine for queries where absence is a normal answer.
- A collector result (`final readonly` class) only where the caller needs every problem at once (a scan of many targets reports each target's error and continues).

## 3. Converting at the edge

- Backend and component AJAX handlers usually need no `try/catch`: October renders `ValidationException` and `ApplicationException` for AJAX requests. Catch only to add a fallback partial or to keep processing a list.
- Use `Flash::success()` for success; errors go through exceptions, not `Flash::error()` + `return`.
- Console commands catch `BusinessException`, `$this->error($e->getMessage())`, `return self::FAILURE`; anything else crashes with a trace.
- Middleware that answers visitors (blocked IP, denied page) renders the plugin's view with the right status; it never leaks the exception.

## 4. Catching and logging

- Catch narrowly; never swallow (`catch (\Throwable) {}` hides incidents). A loop over independent targets may catch per item, record the failure in the result and log it.
- Wrap low-level errors with `previous:` when crossing a boundary (`throw new ProbeFailed($host, previous: $e)`).
- Log unexpected errors once, with context in an array; never log secrets, `.env`, `auth.json`, cookies or full request bodies.

## Checklist

- [ ] Field problems → `ValidationException`; business refusals → a named `BusinessException`; bugs propagate.
- [ ] Services never signal failure with `false`/`null`/status arrays.
- [ ] User-facing messages are translated and contain no internals.
- [ ] No swallowed exceptions; each unexpected error logged once with context.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
