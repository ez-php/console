<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Console\CommandDefinition;
use EzPhp\Console\CommandInterface;
use EzPhp\Console\CompletionGenerator;
use EzPhp\Console\HasDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Class CompletionGeneratorTest
 *
 * @package Tests
 */
#[CoversClass(CompletionGenerator::class)]
#[UsesClass(CommandDefinition::class)]
final class CompletionGeneratorTest extends TestCase
{
    /**
     * @return CommandInterface
     */
    private function makeSimpleCommand(string $name): CommandInterface
    {
        return new readonly class ($name) implements CommandInterface {
            public function __construct(private string $commandName)
            {
            }

            public function getName(): string
            {
                return $this->commandName;
            }

            public function getDescription(): string
            {
                return "Description for {$this->commandName}";
            }

            public function getHelp(): string
            {
                return '';
            }

            public function handle(array $args): int
            {
                return 0;
            }
        };
    }

    /**
     * @return CommandInterface&HasDefinition
     */
    private function makeCommandWithOptions(string $name): CommandInterface
    {
        return new readonly class ($name) implements CommandInterface, HasDefinition {
            public function __construct(private string $commandName)
            {
            }

            public function getName(): string
            {
                return $this->commandName;
            }

            public function getDescription(): string
            {
                return "Description for {$this->commandName}";
            }

            public function getHelp(): string
            {
                return '';
            }

            public function handle(array $args): int
            {
                return 0;
            }

            public function getDefinition(): CommandDefinition
            {
                return (new CommandDefinition())
                    ->option('force', 'f', 'Skip confirmation')
                    ->option('dry-run', '', 'Preview only');
            }
        };
    }

    public function testBashListsAllCommandNames(): void
    {
        $generator = new CompletionGenerator([
            $this->makeSimpleCommand('migrate'),
            $this->makeSimpleCommand('db:seed'),
        ]);

        $script = $generator->bash();

        $this->assertStringContainsString('migrate', $script);
        $this->assertStringContainsString('db:seed', $script);
    }

    public function testBashRegistersCompletionForProgramName(): void
    {
        $generator = new CompletionGenerator([$this->makeSimpleCommand('migrate')]);

        $script = $generator->bash('ez');

        $this->assertStringContainsString('complete -F _ez_complete ez', $script);
    }

    public function testBashUsesCustomProgramName(): void
    {
        $generator = new CompletionGenerator([$this->makeSimpleCommand('migrate')]);

        $script = $generator->bash('myapp');

        $this->assertStringContainsString('complete -F _myapp_complete myapp', $script);
    }

    public function testBashIncludesOptionsForCommandsWithDefinition(): void
    {
        $generator = new CompletionGenerator([$this->makeCommandWithOptions('migrate')]);

        $script = $generator->bash();

        $this->assertStringContainsString('--force', $script);
        $this->assertStringContainsString('--dry-run', $script);
    }

    public function testZshListsAllCommandNamesWithDescriptions(): void
    {
        $generator = new CompletionGenerator([
            $this->makeSimpleCommand('migrate'),
            $this->makeSimpleCommand('db:seed'),
        ]);

        $script = $generator->zsh();

        $this->assertStringContainsString('migrate', $script);
        $this->assertStringContainsString('Description for migrate', $script);
        $this->assertStringContainsString('db:seed', $script);
    }

    public function testZshDeclaresCompdefForProgramName(): void
    {
        $generator = new CompletionGenerator([$this->makeSimpleCommand('migrate')]);

        $script = $generator->zsh('ez');

        $this->assertStringContainsString('#compdef ez', $script);
    }

    public function testZshIncludesOptionsForCommandsWithDefinition(): void
    {
        $generator = new CompletionGenerator([$this->makeCommandWithOptions('migrate')]);

        $script = $generator->zsh();

        $this->assertStringContainsString('--force', $script);
        $this->assertStringContainsString('--dry-run', $script);
    }

    public function testEmptyCommandListStillProducesValidScripts(): void
    {
        $generator = new CompletionGenerator([]);

        $this->assertStringContainsString('complete -F', $generator->bash());
        $this->assertStringContainsString('#compdef', $generator->zsh());
    }
}
