# CLAUDE.md

@AGENTS.md

## Claude Code

- Skills in `.claude/skills/`: `fortify-security` (read it for any change to what a request can do or who gets through), `plugin-upgrades` (anything that reaches an installed site), `plugin-testing`, `testing-best-practices`, and October's own `octobercms-plugin-development`, `octobercms-model-development`, `octobercms-backend-controllers`, `octobercms-ajax-framework`.
- A changed PHP file is formatted by the `PostToolUse` hook in `.claude/settings.json`; still run `make ready` before you say a change is done, and report its result.
- The core lives in the sibling repository `../oc-fortify-plugin`. To try a core change here before it is on GitHub, point the `repositories` entry of `composer.json` at it temporarily and never commit that.
