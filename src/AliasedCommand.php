<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class AliasedCommand
 *
 * Wraps an existing CommandInterface and exposes it under a different name.
 *
 * Usage (in ConsoleServiceProvider or application bootstrap):
 *   new AliasedCommand($app->make(MigrateCommand::class), 'db:migrate')
 *
 * The alias is a fully independent command entry in the Console registry.
 * All other behaviour (description, help, handle) is delegated to the wrapped command.
 *
 * @package EzPhp\Console
 */
final readonly class AliasedCommand implements CommandInterface
{
    /**
     * AliasedCommand Constructor
     *
     * @param CommandInterface $inner The original command to delegate to.
     * @param string           $alias The alternative command name.
     */
    public function __construct(
        private CommandInterface $inner,
        private string $alias,
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->inner->getDescription();
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return $this->inner->getHelp();
    }

    /**
     * @param list<string> $args
     *
     * @return int
     */
    public function handle(array $args): int
    {
        return $this->inner->handle($args);
    }
}
