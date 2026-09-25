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
1. `sync_guidelines.php --check` — fails if any `CLAUDE.md` has drifted from this file
2. `check_test_classes.php` — fails on a duplicate test class name (all packages share the `Tests\` namespace, so a collision is a fatal error in the aggregated run, not a test failure)
3. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
4. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
   *(Note: `@PHP85Migration` does not exist yet in php-cs-fixer; `@PHP83Migration` is the highest available and is used intentionally even though the project targets PHP 8.5)*
5. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse             # PHPStan only
composer cs                  # CS Fixer only
composer test                # PHPUnit only
composer guidelines:check    # CLAUDE.md drift only
composer test-classes:check  # duplicate test class names only
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
- Concrete classes are `final` — extend behavior through composition, not inheritance. Exception-hierarchy base classes (e.g. `EzPhpException`, `HttpException`, `CacheException`) are one carve-out, since they exist specifically to be extended. A documented template-method-style base class (e.g. `Mailable`, meant to be configured via constructor-time subclassing) is the other — the owning module's `CLAUDE.md` must record it under Design Decisions.

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

**Do not edit part 1 by hand.** It is generated from `CODING_GUIDELINES.md` by
`sync_guidelines.php` at the project root:

```
php sync_guidelines.php            # rewrite every out-of-sync CLAUDE.md
php sync_guidelines.php --check    # report drift, exit 1 if any (CI / pre-commit)
```

Edit `CODING_GUIDELINES.md`, then run the script — it replaces everything before the
`# Package:` / `# Directory:` / `# Project:` heading and preserves the hand-written
section below it byte-for-byte. Editing a single copy only creates drift; before this
script existed, all 40 copies had diverged.

### 3 — Scaffolding a new module

`make_module.php` at the project root writes the required-file set and the monorepo
wiring in one step, wrapping `docker-init` for the Docker subset:

```
composer module:make <name> -- --description="..."
php make_module.php <name> --description="..." --services=mysql,redis
```

`<name>` is the kebab-case package name; the namespace is derived as
`EzPhp\<PascalCase>` (each `-`-separated word upper-cased) unless `--namespace=`
overrides it. Existing exceptions the guess gets wrong: `bignum` → `BigNum`,
`dataloader` → `DataLoader`, `dotenv` → `Env`, `graphql` → `GraphQL`, `oauth` → `OAuth`,
`opcache` → `OPCache`, `swagger-ui` → `SwaggerUI`, `webauthn` → `WebAuthn` and
`websocket` → `WebSocket`; `websocket-client` → `WebsocketClient`, `websocket-tls` → `WebsocketTls`,
`webauthn-metadata` → `WebauthnMetadata` and `metrics-statsd` → `MetricsStatsd` are
intentional lower-case-word namespaces, and `testing-application` shares `EzPhp\Testing\`
with `testing`).

To bring in a module whose code already lives in its own repository instead of
generating a fresh skeleton, pass `--repo=` with a git URL:

```
php make_module.php <name> --repo=<git-url> [--namespace=Foo]
```

This runs `git submodule add <url> modules/<name>` instead of writing package
files, then applies the same monorepo wiring below. It is mutually exclusive
with `--services` and `--description` — a submodule brings its own Docker
scaffold (if any) and its own `composer.json` description. A minimal `CLAUDE.md`
stub is written only if the submodule doesn't already ship one, so
`composer guidelines:sync` has a `# Package:` heading to anchor part 1 against.

It writes `modules/<name>/` and registers the module in the four places the monorepo
needs it — root `composer.json` (`autoload.psr-4` **and** the shared
`autoload-dev` `Tests\` directory list), `phpstan.neon`, `phpunit.xml` (test suite
**and** coverage source), and `packages.sh` (alphabetical position) — in both
generated and `--repo` mode.

Two things stay manual on purpose:

- **`CLAUDE.md` part 1** — only the `# Package:` section is generated. Run
  `composer guidelines:sync` afterwards; baking a guidelines copy into the generator
  would recreate the drift the sync script exists to prevent.
- **The host-port table below** (`--services` only) — claim the "next free" row by
  editing the table in `CODING_GUIDELINES.md` (never in a `CLAUDE.md` copy) and run
  `composer guidelines:sync` in the same change. Editing it drifts every `CLAUDE.md`
  until the sync runs, which is why the generator only reminds you instead of doing
  it. Skipping the edit leaves "next free" stale, so the next module collides.

### 4 — Docker scaffold

Run from the new module root (requires `"ez-php/docker": "^2.0"` in `require-dev`):

```
vendor/bin/docker-init
```

This copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the module, replacing `{{MODULE_NAME}}` placeholders. Existing files are never overwritten.

Pass `--services` to merge MySQL/Redis/Meilisearch service definitions directly into `docker-compose.yml` and uncomment the matching sections in `.env.example`, instead of adapting them by hand afterward:

```
vendor/bin/docker-init --services=mysql
vendor/bin/docker-init --services=redis
vendor/bin/docker-init --services=meilisearch
vendor/bin/docker-init --services=mysql,redis
```

Pass `--extensions` to merge PHP extension install blocks (apt packages plus `docker-php-ext-install`/`pecl` lines) directly into `docker/app/Dockerfile`, instead of hand-editing it afterward — supported extensions: `bcmath`, `gmp`, `gd`, `imagick`:

```
vendor/bin/docker-init --extensions=gmp,bcmath
vendor/bin/docker-init --extensions=gd,imagick
```

When run from a module directory inside this monorepo, any requested extension not already present is also merged into the shared root `docker/app/Dockerfile` — the container `composer full` at the root actually runs against, distinct from the module's own standalone image.

After scaffolding:

1. Adapt `docker-compose.yml` — add or remove services (MySQL, Redis, Meilisearch) as needed
2. Adapt `.env.example` — fill in connection defaults matching the services above
3. Assign a unique host port for each exposed service (see table below)

**Allocated host ports:**

| Package | `DB_HOST_PORT` (MySQL) | Redis host port | `MEILISEARCH_PORT` |
|---|---|---|---|
| root (`ez-php-project`) | 3306 | 6379 (`REDIS_PORT`) | 7700 |
| `ez-php/framework` | 3307 | — | — |
| `ez-php/` (application template) | 3308 | 6383 (`REDIS_PORT`) | — |
| `ez-php/orm` | 3309 | — | — |
| `ez-php/cache` | — | 6380 (`REDIS_HOST_PORT`) | — |
| `ez-php/queue` | 3310 | 6381 (`REDIS_HOST_PORT`) | — |
| `ez-php/rate-limiter` | — | 6382 (`REDIS_HOST_PORT`) | — |
| `ez-php/search` | — | — | 7701 |
| `ez-php/event-store` | 3311 | — | — |
| **next free** | **3312** | **6384** | **7702** |

Only set a port for services the module actually uses. Modules without external services need no port config.

> The `MEILISEARCH_PORT` column is the **host** port. Inside a Compose network the service is always reachable at `http://meilisearch:7700` regardless of the host mapping — only publish-side ports need to be unique.

> The "Redis host port" column is likewise the **host**-published port. `ez-php/cache`, `ez-php/queue`, and `ez-php/rate-limiter` map it through a separate `REDIS_HOST_PORT` env var in `docker-compose.yml`, keeping `REDIS_PORT` fixed at `6379` for in-container connections (the app container always reaches Redis at `redis:6379` over the Compose network, regardless of the host mapping) — the root project and the `ez-php/` application template are the two exceptions, since both have no host/container split and use `REDIS_PORT` for both (the template's other in-container Redis settings — `CACHE_REDIS_PORT`, `QUEUE_REDIS_PORT`, `RATE_LIMITER_REDIS_PORT` — stay fixed at `6379` regardless, same as every other module).

> This table tracks only MySQL, Redis, and Meilisearch ports — the three services shared across multiple modules where a collision is otherwise easy to introduce. Mailpit is the one other service with published host ports: SMTP `1025` and web UI `8025`. `ez-php/mail` maps them through `MAILPIT_SMTP_HOST_PORT`/`MAILPIT_API_HOST_PORT` in `modules/mail/docker-compose.yml` (mirroring the `*_HOST_PORT` pattern above, documented in `modules/mail/.env.example`); the root project and the `ez-php/` template each run their own Mailpit on the same defaults (`MAIL_PORT`/`MAIL_WEB_PORT`), so **these three stacks cannot run at the same time** without overriding those variables. It isn't a table column because no module beyond those three runs Mailpit — but a new module adding its own single-use service's ports should likewise parameterize them and document the defaults in its own `.env.example` rather than adding a column here.

### 5 — Monorepo scripts

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
├── OptionDefinition.php      — Value object: a single named option (name, short alias, description)
└── CompletionGenerator.php   — Renders bash/zsh shell-completion scripts from a list of registered commands, reusing HasDefinition metadata for per-command option completion

tests/
├── TestCase.php                        — Base PHPUnit test case
├── Console/ConsoleTest.php             — Covers Console: dispatch, --help, HasDefinition rendering, unknown command
├── Console/InputTest.php               — Covers Input: positional args, --flag, --key=value, short flags
├── Console/OutputTest.php              — Covers Output: ANSI output, ASCII table, progressBar factory
├── Console/ProgressBarTest.php         — Covers ProgressBar: advance, finish, percentage, overflow
├── Console/PromptTest.php              — Covers Prompt: ask, confirm, choice — injected MemoryInputStream
├── Console/CommandDefinitionTest.php   — Covers CommandDefinition + ArgumentDefinition + OptionDefinition
├── Console/AliasedCommandTest.php      — Covers AliasedCommand: name override, delegation, Console dispatch
└── CompletionGeneratorTest.php         — Covers CompletionGenerator: bash/zsh command listing, program-name substitution, per-command option completion via HasDefinition, empty command list
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

### CompletionGenerator (`src/CompletionGenerator.php`)

Renders bash (`bash(string $programName = 'ez'): string`) and zsh (`zsh(string $programName = 'ez'): string`) shell-completion scripts from a `list<CommandInterface>` passed to the constructor. Command-name completion works for every command; per-command option completion (`--force`, `--dry-run`, …) is added only for commands implementing `HasDefinition` — commands without a definition still complete by name, just without their options. Framework core's `completion:generate` command (`framework/src/Console/Command/CompletionGenerateCommand.php`) is the only consumer, invoked with the same `$commands` list built for `ListCommand`/`Console` itself; this module ships the generator, not the command, since only an application actually knows its full command list.

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
- **`CompletionGenerator` only lists commands and (when available) their long options** — no positional-argument completion, no value completion for an option (e.g. suggesting queue names for `queue:work`'s positional argument). Argument/value completion would need per-command, per-argument hints that `CommandDefinition` doesn't carry today; the generator sticks to what the existing metadata already supports rather than inventing a second, richer definition format just for shell completion.

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
