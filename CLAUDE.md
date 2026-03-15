# Coding Guidelines

Applies to the entire ez-php project — framework core, all modules, and the application template.

---

## Environment

- PHP **8.5**, Composer for dependency management
- All commands run **inside Docker** — never directly on the host

```
docker compose exec app <command>
```

Container name: `ez-php-app`, service name: `app`.

---

## Quality Suite

Run after every change:

```
docker compose exec app composer full
```

Executes in order:
1. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
2. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
3. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse   # PHPStan only
composer cs        # CS Fixer only
composer test      # PHPUnit only
```

**PHPStan:** never suppress with `@phpstan-ignore-line` — always fix the root cause.

---

## Coding Standards

- `declare(strict_types=1)` at the top of every PHP file
- Typed properties, parameters, and return values — avoid `mixed`
- PHPDoc on every class and public method
- One responsibility per class — keep classes small and focused
- Constructor injection — no service locator pattern
- No global state unless intentional and documented

**Naming:**

| Thing | Convention |
|---|---|
| Classes / Interfaces | `PascalCase` |
| Methods / variables | `camelCase` |
| Constants | `UPPER_CASE` |
| Files | Match class name exactly |

**Principles:** SOLID · KISS · DRY · YAGNI

---

## Workflow & Behavior

- Write tests **before or alongside** production code (test-first)
- Read and understand the relevant code before making any changes
- Modify the minimal number of files necessary
- Keep implementations small — if it feels big, it likely belongs in a separate module
- No hidden magic — everything must be explicit and traceable
- No large abstractions without clear necessity
- No heavy dependencies — check if PHP stdlib suffices first
- Respect module boundaries — don't reach across packages
- Keep the framework core small — what belongs in a module stays there
- Document architectural reasoning for non-obvious design decisions
- Do not change public APIs unless necessary
- Prefer composition over inheritance — no premature abstractions

---

## New Modules & CLAUDE.md Files

When creating a new module or `CLAUDE.md` anywhere in this repository:

**CLAUDE.md structure:**
- Start with the full content of `CODING_GUIDELINES.md`, verbatim
- Then add `---` followed by `# Package: ez-php/<name>` (or `# Directory: <name>`)
- Module-specific section must cover:
  - Source structure (file tree with one-line descriptions per file)
  - Key classes and their responsibilities
  - Design decisions and constraints
  - Testing approach and any infrastructure requirements (e.g. needs MySQL, Redis)
  - What does **not** belong in this module

**Each module needs its own:**
`composer.json` · `phpstan.neon` · `phpunit.xml` · `.php-cs-fixer.php` · `.gitignore` · `.github/workflows/ci.yml` · `README.md` · `tests/TestCase.php`

**Docker setup:**   
run `vendor/bin/docker-init` from the new module root to scaffold Docker files (requires `"ez-php/docker": "0.*"` in `require-dev`). The script reads the package name from `composer.json`, copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the project, replacing `{{MODULE_NAME}}` placeholders — skips files that already exist. After scaffolding, adapt `docker-compose.yml` and `.env.example` for the module's required services (MySQL, Redis, etc.) and set a unique `DB_PORT` — increment by one per package starting with `3306` (root).

---

# Package: ez-php/console

Minimal console infrastructure — command dispatch, argument/option parsing, and ANSI output helpers.

This package is a **zero-framework dependency** standalone library. It has no knowledge of the Application, Container, or any other ez-php package. The framework wires it up via `ConsoleServiceProvider` in `ez-php/framework`.

---

## Source Structure

```
src/
├── CommandInterface.php   — Contract for all console commands: getName/getDescription/getHelp/handle
├── Console.php            — Command registry and dispatcher; parses argv, routes to command, prints usage
├── Input.php              — Parses a raw argv token list into positional arguments and named options/flags
└── Output.php             — Static helpers for colored ANSI terminal output (line/info/success/error/warning/colorize)

tests/
├── TestCase.php                  — Base PHPUnit test case
├── Console/ConsoleTest.php       — Covers Console: dispatch, --help, unknown command, empty argv
├── Console/InputTest.php         — Covers Input: positional args, --flag, --key=value, defaults
└── Console/OutputTest.php        — Covers Output: stdout/stderr output, ANSI codes
```

---

## Key Classes and Responsibilities

### CommandInterface (`src/CommandInterface.php`)

The contract every command must implement.

| Method | Return | Meaning |
|---|---|---|
| `getName()` | `string` | Command name as typed on the CLI, e.g. `'migrate'` or `'make:controller'` |
| `getDescription()` | `string` | One-line summary shown in the usage listing |
| `getHelp()` | `string` | Extended help text shown with `--help`; return `''` to fall back to description only |
| `handle(array $args)` | `int` | Execute the command; `$args` is the raw token list after the command name, **without** `--help`; return exit code (`0` = success, non-zero = error) |

`$args` passed to `handle()` has `--help` filtered out by `Console`. Commands should parse `$args` using `Input`.

---

### Console (`src/Console.php`)

Command registry and dispatcher. Constructed with a `list<CommandInterface>`.

**`run(array $argv): int`**

| Scenario | Behaviour |
|---|---|
| `$argv[1]` absent | Prints usage listing, returns `0` |
| `$argv[1]` matches a command + `--help` in args | Prints command help, returns `0` |
| `$argv[1]` matches a command | Filters `--help` from args, calls `handle($filtered)`, returns its exit code |
| `$argv[1]` matches no command | Writes `"Unknown command: $name"` to `STDERR`, prints usage, returns `1` |

Usage listing format: `ez <command> [arguments]` followed by each command name (padded to 24 chars, green) and its description.

---

### Input (`src/Input.php`)

Parses a raw `list<string>` of argv tokens (everything after the command name) into two categories:

- **Positional arguments** — tokens that do not start with `--`
- **Options** — tokens starting with `--`
  - `--name=value` → `option('name') === 'value'`
  - `--flag` → `hasFlag('flag') === true`, `option('flag') === ''`

| Method | Signature | Behaviour |
|---|---|---|
| `argument` | `argument(int $index): ?string` | Zero-based positional argument, or `null` |
| `arguments` | `arguments(): list<string>` | All positional arguments |
| `option` | `option(string $name, string $default = ''): string` | Named option value; `''` for bare flags; `$default` if absent |
| `hasFlag` | `hasFlag(string $name): bool` | `true` if `--name` or `--name=value` was present |

---

### Output (`src/Output.php`)

Static helpers for terminal output. All methods write a trailing `\n`.

| Method | Stream | ANSI color |
|---|---|---|
| `line(string $text = '')` | stdout | none |
| `info(string $text)` | stdout | blue (34) |
| `success(string $text)` | stdout | green (32) |
| `warning(string $text)` | stdout | yellow (33) |
| `error(string $text)` | **stderr** | red (31) |
| `colorize(string $text, int $code): string` | — | wraps in `\e[{code}m…\e[0m` |

`error()` writes to `STDERR`; all others write to `STDOUT`. `colorize()` is a pure string transformer used internally by `Console` for formatting the usage listing.

---

## Design Decisions and Constraints

- **Zero framework dependencies** — This package must remain usable without `ez-php/framework`. It must not import Application, Container, Config, or any other framework class. Framework integration is the responsibility of `ConsoleServiceProvider` in `ez-php/framework`.
- **`--help` is handled by Console, not commands** — Commands never see `--help` in their `$args`. This keeps command logic clean and ensures consistent help behaviour across all commands.
- **Static `Output` methods** — Output is treated as a terminal utility, not an injectable service. This keeps command implementations simple (`Output::success(...)` vs. constructor-injected output object). If testability of output becomes a concern, capture via output buffering in tests.
- **`Input` is not injected by Console** — `Console` passes the raw `$args` array to `handle()`. Commands construct their own `Input` from it. This keeps `Console` decoupled from `Input` and lets commands that don't need parsing skip the allocation.
- **Exit codes are the command's responsibility** — `Console::run()` returns whatever `handle()` returns verbatim. The application's entry point (`artisan`/`ez`) should pass this to `exit()`.
- **No command grouping or aliases** — Command names are plain strings. Grouping conventions (e.g. `make:*`) are by naming only, not by structure. Adding aliases or subcommand routing is out of scope.

---

## Testing Approach

- **No external infrastructure required** — All tests are purely in-process. No filesystem, no database, no network.
- **Output testing** — Use `$this->expectOutputString()` or output buffering (`ob_start` / `ob_get_clean()`) to assert what `Console` and `Output` write to stdout. For `STDERR` output, redirect or capture within the test.
- **`Input` tests** — Construct `Input` with a raw token array and assert `argument()`, `option()`, and `hasFlag()` results directly.
- **`Console` tests** — Pass a minimal `list<CommandInterface>` (anonymous class or stub) and a crafted `$argv` array to `run()`. Assert the return code and any captured output.
- **`#[UsesClass]` required** — PHPUnit is configured with `beStrictAboutCoverageMetadata=true`. Declare indirectly used classes with `#[UsesClass]`.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| Concrete commands (migrate, make:*) | `ez-php/framework` (`src/Console/Command/`) |
| Wiring Console into the Application | `ez-php/framework` (`ConsoleServiceProvider`) |
| Interactive prompts (readline, question/confirm) | Future `ez-php/console` extension or application layer |
| Progress bars or tables | Application layer or a future output extension |
| Scheduled / cron commands | `ez-php/queue` or application layer |
| Coloured log output for HTTP requests | `ez-php/logging` |
