# CLAUDE.md

@AGENTS.md

## Claude Code

- Skills in `.claude/skills/`: the architecture skills (`application-layer`, `dependency-injection`, `error-handling`, `validation`, `events`, `testing-architecture`, `domain-layer-cqrs`, `plugin-boundaries`), `fortify-security` (read it for any change to what a request can do or who gets through), `plugin-upgrades` (anything that reaches an installed site), `plugin-testing`, `testing-best-practices`, and October's own `octobercms-plugin-development`, `octobercms-model-development`, `octobercms-backend-controllers`, `octobercms-ajax-framework`.
- A changed PHP file is formatted by the `PostToolUse` hook in `.claude/settings.json`; still run `make ready` before you say a change is done, and report its result.
- The core lives in the sibling repository `../oc-fortify-plugin`. To try a core change here before it is released, add a temporary `path` repository pointing at it and `composer require wobqqq/fortify-plugin:dev-main`; never commit that.
- Never push to `main`: work on a branch and open a pull request (see *Git workflow* in AGENTS.md). Write everything in English.
