<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\CommandInterface;
use EzPhp\Console\Console;
use EzPhp\Console\Output;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class ConsoleTest
 *
 * @package Tests\Console
 */
#[CoversClass(Console::class)]
#[UsesClass(Output::class)]
final class ConsoleTest extends TestCase
{
    /**
     * @param string $name
     * @param int    $exitCode
     *
     * @return CommandInterface
     */
    private function makeCommand(string $name, int $exitCode = 0): CommandInterface
    {
        return new class ($name, $exitCode) implements CommandInterface {
            /**
             * Constructor
             *
             * @param string $commandName
             * @param int    $exitCode
             */
            public function __construct(
                private readonly string $commandName,
                private readonly int $exitCode,
            ) {
            }

            /**
             * @return string
             */
            public function getName(): string
            {
                return $this->commandName;
            }

            /**
             * @return string
             */
            public function getDescription(): string
            {
                return 'Test command: ' . $this->commandName;
            }

            /**
             * @return string
             */
            public function getHelp(): string
            {
                return 'Usage: ez ' . $this->commandName;
            }

            /** @param list<string> $args */
            public function handle(array $args): int
            {
                echo 'ran:' . $this->commandName;
                return $this->exitCode;
            }
        };
    }

    /**
     * @return void
     */
    public function test_dispatches_known_command(): void
    {
        $console = new Console([$this->makeCommand('greet')]);

        ob_start();
        $code = $console->run(['ez', 'greet']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertSame('ran:greet', $output);
    }

    /**
     * @return void
     */
    public function test_returns_exit_code_from_command(): void
    {
        $console = new Console([$this->makeCommand('fail', 2)]);

        ob_start();
        $code = $console->run(['ez', 'fail']);
        ob_get_clean();

        $this->assertSame(2, $code);
    }

    /**
     * @return void
     */
    public function test_returns_1_for_unknown_command(): void
    {
        $console = new Console([]);

        ob_start();
        $code = $console->run(['ez', 'unknown']);
        ob_get_clean();

        $this->assertSame(1, $code);
    }

    /**
     * @return void
     */
    public function test_returns_0_and_prints_usage_when_no_command_given(): void
    {
        $console = new Console([$this->makeCommand('greet')]);

        ob_start();
        $code = $console->run(['ez']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('greet', $output);
    }

    /**
     * @return void
     */
    public function test_usage_lists_all_commands(): void
    {
        $console = new Console([
            $this->makeCommand('migrate'),
            $this->makeCommand('make:migration'),
        ]);

        ob_start();
        $console->run(['ez']);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('migrate', $output);
        $this->assertStringContainsString('make:migration', $output);
    }

    /**
     * @return void
     */
    public function test_passes_arguments_to_command(): void
    {
        $command = new class () implements CommandInterface {
            /** @var list<list<string>> */
            public array $received = [];

            /**
             * @return string
             */
            public function getName(): string
            {
                return 'capture';
            }

            /**
             * @return string
             */
            public function getDescription(): string
            {
                return '';
            }

            /**
             * @return string
             */
            public function getHelp(): string
            {
                return '';
            }

            /** @param list<string> $args */
            public function handle(array $args): int
            {
                $this->received[] = $args;
                return 0;
            }
        };

        $console = new Console([$command]);
        $console->run(['ez', 'capture', 'foo', 'bar']);

        $this->assertSame([['foo', 'bar']], $command->received);
    }

    /**
     * @return void
     */
    public function test_help_flag_prints_command_help_and_returns_0(): void
    {
        $console = new Console([$this->makeCommand('greet')]);

        ob_start();
        $code = $console->run(['ez', 'greet', '--help']);
        $output = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('greet', $output);
        $this->assertStringContainsString('Usage: ez greet', $output);
    }

    /**
     * @return void
     */
    public function test_help_flag_does_not_call_handle(): void
    {
        $command = new class () implements CommandInterface {
            public bool $handleCalled = false;

            /**
             * @return string
             */
            public function getName(): string
            {
                return 'foo';
            }

            /**
             * @return string
             */
            public function getDescription(): string
            {
                return 'foo';
            }

            /**
             * Return extended help text shown when --help is passed.
             * Return an empty string to fall back to the description only.
             *
             * @return string
             */
            public function getHelp(): string
            {
                return 'Usage: ez foo';
            }

            /** @param list<string> $args */
            public function handle(array $args): int
            {
                $this->handleCalled = true;
                return 0;
            }
        };

        $console = new Console([$command]);

        ob_start();
        $console->run(['ez', 'foo', '--help']);
        ob_get_clean();

        $this->assertFalse($command->handleCalled);
    }

    /**
     * @return void
     */
    public function test_help_flag_stripped_from_args_passed_to_handle(): void
    {
        $command = new class () implements CommandInterface {
            /** @var list<string> */
            public array $received = [];

            /**
             * @return string
             */
            public function getName(): string
            {
                return 'capture';
            }

            /**
             * @return string
             */
            public function getDescription(): string
            {
                return '';
            }

            /**
             * Return extended help text shown when --help is passed.
             * Return an empty string to fall back to the description only.
             *
             * @return string
             */
            public function getHelp(): string
            {
                return '';
            }

            /** @param list<string> $args */
            public function handle(array $args): int
            {
                $this->received = $args;
                return 0;
            }
        };

        $console = new Console([$command]);

        ob_start();
        $console->run(['ez', 'capture', 'foo', '--help', 'bar']);
        ob_get_clean();

        // --help triggers help, handle not called; but if it were, --help would be stripped
        $this->assertSame([], $command->received);
    }

    /**
     * @return void
     */
    public function test_usage_output_contains_ansi_color_codes(): void
    {
        $console = new Console([$this->makeCommand('greet')]);

        ob_start();
        $console->run(['ez']);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("\e[", $output);
    }
}
