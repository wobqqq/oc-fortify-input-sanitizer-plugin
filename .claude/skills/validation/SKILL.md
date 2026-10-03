---
name: validation
description: "Which check goes where in an October plugin. Use when editing a model's $rules, $attributeNames or $customMessages (October\\Rain\\Database\\Traits\\Validation), adding rules from backend.form.extendFields or Model::extend, validating component/AJAX input with Validator::make, checking a state or limit before an action (already blocked, limit reached, would lock the administrator out), validating settings values read from a SettingModel, or designing a DTO or value object (Ip, Cidr, Url, Port, Email)."
license: MIT
---

# Validation in an October plugin: two levels

| Level | Question | Where | Failure |
|---|---|---|---|
| **Input** | Is the submitted value well-formed? type, required, format, length, `ip`, `url:http,https`, `between` | the model's `$rules` (Validation trait) for backend forms; `Validator::make()` in component/AJAX handlers | `ValidationException` → field error |
| **Business** | May this happen now? existence, state, limits, "would lock the current administrator out" | the service, using DTOs and value objects | the plugin's `BusinessException` |

## 1. Model rules: shape only

- `$rules` check what a form field must look like. Keep business conditions and queries about other records out of them.
- Every settings value is untrusted when **read** too: a transformer re-validates and casts it into a DTO (a value saved by an older version, edited in the database or imported may be invalid). Skip or default invalid entries; never let them reach a service as raw strings.
- When the plugin adds fields to another model's form, add their rules in the same place (`Model::extend()` + `backend.form.extendFields`) so they cannot be saved unvalidated.

## 2. Services re-check business rules

- A service is called from forms, AJAX handlers, console commands and other plugins; it does not assume the caller validated business state.
- Rules that protect the site from its own administrator (lock-out, disabling the last admin, a deny-all list) live in the service and run on every path, including console recovery commands.
- Uniqueness and relations that must hold under concurrency are also database constraints (unique index, foreign key) in the plugin's migrations.

## 3. Value objects for values with rules

```php
final readonly class Cidr
{
    private function __construct(public string $network, public int $prefix) {}

    public static function fromString(string $value): self
    {
        [$ip, $prefix] = array_pad(explode('/', trim($value), 2), 2, null);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException(sprintf('"%s" is not an IP network.', $value));
        }
        $max = str_contains($ip, ':') ? 128 : 32;
        $bits = $prefix === null ? $max : (ctype_digit($prefix) ? (int)$prefix : -1);
        if ($bits < 0 || $bits > $max) {
            throw new \InvalidArgumentException(sprintf('"%s" has an invalid prefix.', $value));
        }

        return new self($ip, $bits);
    }
}
```

- Construction validates; holders can trust the value. Put behaviour on it (`contains(Ip $ip)`), not in callers.
- A value object throws `\InvalidArgumentException`: a programmer error. User input reaches it only after input validation; transformers reading stored settings catch it and drop/flag the invalid entry.

## 4. Front-end components

- Validate AJAX input with `Validator::make($data, $rules)` and `throw new ValidationException($validator)` so the AJAX framework shows field errors.
- Never trust hidden fields or the handler name for authorization; check permissions/ownership in the handler or service.

## Checklist

- [ ] `$rules` check field shape only; extended forms add their rules too.
- [ ] Stored settings are re-validated when read, through one transformer into DTOs.
- [ ] Services enforce state, limits and lock-out protection on every path.
- [ ] Values with rules are value objects; their exception is a programmer error.

Based on the ideas of "Architecture of Complex Web Applications" by Adel Fayzrakhmanov — https://github.com/adelf/acwa_book_ru
