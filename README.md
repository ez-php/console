# ez-php/console

Lightweight console infrastructure for PHP — command dispatcher, argument parser, and colored output helpers.

## Installation

```bash
composer require ez-php/console
```

## Usage

### Defining a command

```php
use EzPhp\Console\CommandInterface;
use EzPhp\Console\Input;
use EzPhp\Console\Output;

class GreetCommand implements CommandInterface
{
    public function getName(): string { return 'greet'; }
    public function getDescription(): string { return 'Greet someone'; }
    public function getHelp(): string { return 'Usage: ez greet <name>'; }

    public function handle(array $args): int
    {
        $input = new Input($args);
        $name = $input->argument(0) ?? 'World';
        Output::success("Hello, $name!");
        return 0;
    }
}
```

### Running the console

```php
use EzPhp\Console\Console;

$console = new Console([new GreetCommand()]);
exit($console->run($argv));
```

### Output helpers

```php
Output::line('plain text');
Output::info('informational message');   // blue
Output::success('it worked!');           // green
Output::warning('be careful');           // yellow
Output::error('something failed');       // red (stderr)
Output::colorize('custom', 35);          // magenta
```

### Parsing arguments

```php
$input = new Input(['foo', '--name=Alice', '--verbose']);

$input->argument(0);          // 'foo'
$input->option('name');       // 'Alice'
$input->option('missing', 'default'); // 'default'
$input->hasFlag('verbose');   // true
```

## Progress bars

```php
$bar = Output::progressBar(100);
foreach ($items as $item) {
    process($item);
    $bar->advance();
}
$bar->finish();
```

## Interactive prompts

```php
use EzPhp\Console\Prompt;

$prompt = new Prompt();
$name   = $prompt->ask('What is your name?');
$ok     = $prompt->confirm('Continue?');
$color  = $prompt->choice('Pick a color', ['red', 'green', 'blue']);
```

## Structured command definitions

Commands implementing `HasDefinition` expose typed argument and option metadata for `--help` rendering:

```php
use EzPhp\Console\HasDefinition;
use EzPhp\Console\CommandDefinition;

class MyCommand implements CommandInterface, HasDefinition
{
    public function getDefinition(): CommandDefinition
    {
        return (new CommandDefinition())
            ->argument('name', 'The user name')
            ->option('force', 'f', 'Skip confirmation');
    }
    // ...
}
```

## Command aliases

```php
use EzPhp\Console\AliasedCommand;

$commands = [
    new AliasedCommand($app->make(MigrateCommand::class), 'db:migrate'),
];
```

## Classes

| Class | Description |
|---|---|
| `Console` | Command registry and dispatcher |
| `CommandInterface` | Contract: `getName`, `getDescription`, `getHelp`, `handle` |
| `HasDefinition` | Optional interface: exposes `CommandDefinition` for structured `--help` |
| `AliasedCommand` | Wraps a command under a different name |
| `Input` | Parses argv tokens into positional arguments and named options/flags |
| `Output` | Static ANSI output helpers; `table()`; `progressBar()` factory |
| `ProgressBar` | In-place terminal progress bar |
| `Prompt` | Interactive prompts: `ask()`, `confirm()`, `choice()` |
| `InputStreamInterface` | Abstraction over a readable line stream (injectable for testing) |
| `StdinInputStream` | `InputStreamInterface` implementation reading from STDIN |
| `CommandDefinition` | Fluent builder for argument + option declarations |
| `ArgumentDefinition` | Value object: positional argument (name, description, required) |
| `OptionDefinition` | Value object: named option (name, short alias, description) |

## License

MIT
