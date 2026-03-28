# Changelog

All notable changes to `ez-php/console` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [v1.0.1] — 2026-03-25

### Changed
- Tightened all `ez-php/*` dependency constraints from `"*"` to `"^1.0"` for predictable resolution

---

## [v1.0.0] — 2026-03-24

### Added
- `Console` — command dispatcher that resolves and executes registered `CommandInterface` implementations
- `CommandInterface` — contract for all CLI commands with `getName()`, `getDescription()`, and `execute(Input, Output): int`
- `Input` — argument and named option parser built on `$argv`
- `Output` — terminal output helper with ANSI color support (info, success, warning, error, line)
