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

## License

MIT
