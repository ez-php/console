# Coding Guidelines

Applies to the entire ez-php project — framework core, all modules, and the application template.

---

## Environment

- PHP **8.5**, Composer for dependency management
- All project based commands run **inside Docker** — never directly on the host

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

### 1 — Required files

Every module under `modules/<name>/` must have:

| File | Purpose |
|---|---|
| `composer.json` | package definition, deps, autoload |
| `phpstan.neon` | static analysis config, level 9 |
| `phpunit.xml` | test suite config |
| `.php-cs-fixer.php` | code style config |
| `.gitignore` | ignore `vendor/`, `.env`, cache |
| `.env.example` | environment variable defaults (copy to `.env` on first run) |
| `docker-compose.yml` | Docker Compose service definition (always `container_name: ez-php-<name>-app`) |
| `docker/app/Dockerfile` | module Docker image (`FROM au9500/php:8.5`) |
| `docker/app/container-start.sh` | container entrypoint: `composer install` → `sleep infinity` |
| `docker/app/php.ini` | PHP ini overrides (`memory_limit`, `display_errors`, `xdebug.mode`) |
| `.github/workflows/ci.yml` | standalone CI pipeline |
| `README.md` | public documentation |
| `tests/TestCase.php` | base test case for the module |
| `start.sh` | convenience script: copy `.env`, bring up Docker, wait for services, exec shell |
| `CLAUDE.md` | see section 2 below |

### 2 — CLAUDE.md structure

Every module `CLAUDE.md` must follow this exact structure:

1. **Full content of `CODING_GUIDELINES.md`, verbatim** — copy it as-is, do not summarize or shorten
2. A `---` separator
3. `# Package: ez-php/<name>` (or `# Directory: <name>` for non-package directories)
4. Module-specific section covering:
   - Source structure — file tree with one-line description per file
   - Key classes and their responsibilities
   - Design decisions and constraints
   - Testing approach and infrastructure requirements (MySQL, Redis, etc.)
   - What does **not** belong in this module

### 3 — Docker scaffold

Run from the new module root (requires `"ez-php/docker": "^1.0"` in `require-dev`):

```
vendor/bin/docker-init
```

This copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the module, replacing `{{MODULE_NAME}}` placeholders. Existing files are never overwritten.

After scaffolding:

1. Adapt `docker-compose.yml` — add or remove services (MySQL, Redis) as needed
2. Adapt `.env.example` — fill in connection defaults matching the services above
3. Assign a unique host port for each exposed service (see table below)

**Allocated host ports:**

| Package | `DB_HOST_PORT` (MySQL) | `REDIS_PORT` |
|---|---|---|
| root (`ez-php-project`) | 3306 | 6379 |
| `ez-php/framework` | 3307 | — |
| `ez-php/orm` | 3309 | — |
| `ez-php/cache` | — | 6380 |
| **next free** | **3311** | **6383** |

Only set a port for services the module actually uses. Modules without external services need no port config.

### 4 — Monorepo scripts

`packages.sh` at the project root is the **central package registry**. Both `push_all.sh` and `update_all.sh` source it — the package list lives in exactly one place.

When adding a new module, add `"$ROOT/modules/<name>"` to the `PACKAGES` array in `packages.sh` in **alphabetical order** among the other `modules/*` entries (before `framework`, `ez-php`, and the root entry at the end).

---

# Package: ez-php/console

Console infrastructure — command dispatch, argument/option parsing, ANSI output helpers, interactive prompts, progress bars, ASCII tables, typed command definitions, and command aliasing.

This package is a **zero-framework dependency** standalone library. It has no knowledge of the Application, Container, or any other ez-php package. The framework wires it up via `ConsoleServiceProvider` in `ez-php/framework`.

---

## Source Structure

```
src/
├── CommandInterface.php      — Contract for all console commands: getName/getDescription/getHelp/handle
├── HasDefinition.php         — Optional interface: commands expose a CommandDefinition for structured --help
├── AliasedCommand.php        — Wraps a CommandInterface and exposes it under a different name
├── Console.php               — Command registry and dispatcher; parses argv, routes to command, prints usage
├── Input.php                 — Parses a raw argv token list into positional arguments and named options/flags
├── Output.php                — Static helpers: colored ANSI output, ASCII table, ProgressBar factory
├── ProgressBar.php           — Renders a terminal progress bar to stdout via carriage return
├── Prompt.php                — Interactive prompts: ask(), confirm(), choice() — reads from InputStreamInterface
├── InputStreamInterface.php  — Abstraction over a readable line stream (used by Prompt; injected for testing)
├── StdinInputStream.php      — InputStreamInterface implementation that reads from STDIN
├── CommandDefinition.php     — Fluent builder for argument + option declarations (used by HasDefinition commands)
├── ArgumentDefinition.php    — Value object: a single positional argument (name, description, required flag)
└── OptionDefinition.php      — Value object: a single named option (name, short alias, description)

tests/
├── TestCase.php                        — Base PHPUnit test case
├── Console/ConsoleTest.php             — Covers Console: dispatch, --help, HasDefinition rendering, unknown command
├── Console/InputTest.php               — Covers Input: positional args, --flag, --key=value, short flags
├── Console/OutputTest.php              — Covers Output: ANSI output, ASCII table, progressBar factory
├── Console/ProgressBarTest.php         — Covers ProgressBar: advance, finish, percentage, overflow
├── Console/PromptTest.php              — Covers Prompt: ask, confirm, choice — injected MemoryInputStream
├── Console/CommandDefinitionTest.php   — Covers CommandDefinition + ArgumentDefinition + OptionDefinition
└── Console/AliasedCommandTest.php      — Covers AliasedCommand: name override, delegation, Console dispatch
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

When printing command help, if the command implements `HasDefinition`, Console also renders structured Arguments and Options sections below the free-text help.

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

Static helpers for terminal output. All text methods write a trailing `\n`.

| Method | Stream | ANSI color |
|---|---|---|
| `line(string $text = '')` | stdout | none |
| `info(string $text)` | stdout | blue (34) |
| `success(string $text)` | stdout | green (32) |
| `warning(string $text)` | stdout | yellow (33) |
| `error(string $text)` | **stderr** | red (31) |
| `colorize(string $text, int $code): string` | — | wraps in `\e[{code}m…\e[0m` |
| `table(list<string> $headers, list<list<string>> $rows): void` | stdout | none — renders an ASCII table |
| `progressBar(int $total, int $width = 40): ProgressBar` | — | factory that creates a `ProgressBar` instance |

`error()` writes to `STDERR`; all others write to `STDOUT`. `colorize()` is a pure string transformer.

---

### ProgressBar (`src/ProgressBar.php`)

Renders a terminal progress bar in-place using a carriage return (`\r`).

```php
$bar = Output::progressBar(100);
foreach ($items as $item) {
    process($item);
    $bar->advance();
}
$bar->finish(); // moves to new line
```

| Method | Behaviour |
|---|---|
| `advance(int $step = 1): void` | Advance by $step (capped at total), redraw bar |
| `finish(): void` | Set progress to 100%, redraw, write newline |

---

### Prompt (`src/Prompt.php`)

Interactive prompts that read from an `InputStreamInterface` (defaults to STDIN).

```php
$prompt = new Prompt();
$name  = $prompt->ask('What is your name?');
$ok    = $prompt->confirm('Continue?');
$color = $prompt->choice('Pick a color', ['red', 'green', 'blue']);
```

Inject a custom stream for testing:
```php
$prompt = new Prompt(new class(['Alice', 'y', '1']) implements InputStreamInterface { ... });
```

| Method | Behaviour |
|---|---|
| `ask(string $question): string` | Prompt + read trimmed line |
| `confirm(string $question): bool` | Prompt + `[y/N]`; `true` for `y`/`yes` |
| `choice(string $question, array $options): string` | Numbered list; returns selected value; throws `InvalidArgumentException` on invalid index |

---

### CommandDefinition + HasDefinition

`HasDefinition` is an optional interface for commands that expose structured argument/option metadata:

```php
class MyCommand implements CommandInterface, HasDefinition {
    public function getDefinition(): CommandDefinition {
        return (new CommandDefinition())
            ->argument('name', 'The user name')
            ->option('force', 'f', 'Skip confirmation');
    }
}
```

`Console` detects `HasDefinition` and renders Arguments/Options sections in `--help` output.

---

### AliasedCommand (`src/AliasedCommand.php`)

Wraps any `CommandInterface` and overrides `getName()` to expose it under a different name. All other calls delegate to the inner command.

```php
$commands = [
    new AliasedCommand($app->make(MigrateCommand::class), 'db:migrate'),
];
```

---

## Design Decisions and Constraints

- **Zero framework dependencies** — This package must remain usable without `ez-php/framework`. It must not import Application, Container, Config, or any other framework class. Framework integration is the responsibility of `ConsoleServiceProvider` in `ez-php/framework`.
- **`--help` is handled by Console, not commands** — Commands never see `--help` in their `$args`. This keeps command logic clean and ensures consistent help behaviour across all commands.
- **Static `Output` methods** — Output is treated as a terminal utility, not an injectable service. This keeps command implementations simple (`Output::success(...)` vs. constructor-injected output object). Capture via output buffering in tests.
- **`Input` is not injected by Console** — `Console` passes the raw `$args` array to `handle()`. Commands construct their own `Input` from it.
- **Exit codes are the command's responsibility** — `Console::run()` returns whatever `handle()` returns verbatim. The application's entry point (`artisan`/`ez`) should pass this to `exit()`.
- **`Prompt` uses `InputStreamInterface`** — Avoids PHP's unrepresentable `resource` type; makes prompts fully testable in-process without piping stdin.
- **`HasDefinition` is optional** — `CommandInterface` is unchanged. Commands that don't need structured help simply skip the interface. No breaking change.
- **`AliasedCommand` is a wrapper, not a registry feature** — Aliases are registered as full entries in the `Console` command list. This avoids complicating `Console`'s dispatch logic.

---

## Testing Approach

- **No external infrastructure required** — All tests are purely in-process. No filesystem, no database, no network.
- **Output testing** — Use output buffering (`ob_start` / `ob_get_clean()`) to assert what `Console`, `Output`, `ProgressBar`, and `Prompt` write to stdout.
- **Prompt testing** — Inject an anonymous `InputStreamInterface` implementation backed by a `list<string>` to simulate user input without touching STDIN.
- **`#[UsesClass]` required** — PHPUnit is configured with `beStrictAboutCoverageMetadata=true`. Declare indirectly used classes with `#[UsesClass]`.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| Concrete commands (migrate, make:*) | `ez-php/framework` (`src/Console/Command/`) |
| Wiring Console into the Application | `ez-php/framework` (`ConsoleServiceProvider`) |
| Command scheduling / cron integration | `ez-php/framework` (`Schedule/Scheduler`, `Command/ScheduleRunCommand`) |
| Coloured log output for HTTP requests | `ez-php/logging` |
