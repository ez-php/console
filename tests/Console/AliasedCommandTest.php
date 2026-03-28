<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\AliasedCommand;
use EzPhp\Console\CommandInterface;
use EzPhp\Console\Console;
use EzPhp\Console\Output;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class AliasedCommandTest
 *
 * @package Tests\Console
 */
#[CoversClass(AliasedCommand::class)]
#[UsesClass(Console::class)]
#[UsesClass(Output::class)]
final class AliasedCommandTest extends TestCase
{
    /**
     * @return CommandInterface
     */
    private function makeInner(): CommandInterface
    {
        return new readonly class () implements CommandInterface {
            public function getName(): string
            {
                return 'migrate';
            }

            public function getDescription(): string
            {
                return 'Run migrations';
            }

            public function getHelp(): string
            {
                return 'Usage: ez migrate';
            }

            /** @param list<string> $args */
            public function handle(array $args): int
            {
                echo 'migrated';

                return 0;
            }
        };
    }

    /**
     * @return void
     */
    public function test_alias_overrides_name(): void
    {
        $command = new AliasedCommand($this->makeInner(), 'db:migrate');

        $this->assertSame('db:migrate', $command->getName());
    }

    /**
     * @return void
     */
    public function test_alias_delegates_description(): void
    {
        $command = new AliasedCommand($this->makeInner(), 'db:migrate');

        $this->assertSame('Run migrations', $command->getDescription());
    }

    /**
     * @return void
     */
    public function test_alias_delegates_help(): void
    {
        $command = new AliasedCommand($this->makeInner(), 'db:migrate');

        $this->assertSame('Usage: ez migrate', $command->getHelp());
    }

    /**
     * @return void
     */
    public function test_alias_delegates_handle(): void
    {
        $command = new AliasedCommand($this->makeInner(), 'db:migrate');

        ob_start();
        $code = $command->handle([]);
        $out = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertSame('migrated', $out);
    }

    /**
     * @return void
     */
    public function test_aliased_command_is_dispatched_by_console(): void
    {
        $alias = new AliasedCommand($this->makeInner(), 'db:migrate');
        $console = new Console([$alias]);

        ob_start();
        $code = $console->run(['ez', 'db:migrate']);
        $out = (string) ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertSame('migrated', $out);
    }
}
