<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class Console
 *
 * @package EzPhp\Console
 */
final readonly class Console
{
    /**
     * Console Constructor
     *
     * @param list<CommandInterface> $commands
     */
    public function __construct(private array $commands)
    {
    }

    /**
     * @param list<string> $argv
     *
     * @return int
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? null;
        $args = array_slice($argv, 2);

        if ($name === null) {
            $this->printUsage();
            return 0;
        }

        foreach ($this->commands as $command) {
            if ($command->getName() === $name) {
                if (in_array('--help', $args, true)) {
                    $this->printCommandHelp($command);
                    return 0;
                }

                $filtered = array_values(array_filter($args, fn (string $a): bool => $a !== '--help'));

                return $command->handle($filtered);
            }
        }

        fwrite(STDERR, "Unknown command: $name\n\n");
        $this->printUsage();

        return 1;
    }

    /**
     * @return void
     */
    private function printUsage(): void
    {
        echo Output::colorize('Usage:', 33) . " ez <command> [arguments]\n\n";
        echo Output::colorize('Available commands:', 33) . "\n";

        foreach ($this->commands as $command) {
            echo sprintf(
                '  %s  %s' . "\n",
                Output::colorize(sprintf('%-24s', $command->getName()), 32),
                $command->getDescription(),
            );
        }
    }

    /**
     * @param CommandInterface $command
     *
     * @return void
     */
    private function printCommandHelp(CommandInterface $command): void
    {
        echo Output::colorize($command->getName(), 33) . ' — ' . $command->getDescription() . "\n";

        $help = $command->getHelp();

        if ($help !== '') {
            echo "\n" . $help . "\n";
        }
    }
}
