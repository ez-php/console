<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class CommandDefinition
 *
 * Fluent builder for describing a command's arguments and options.
 * Used by HasDefinition commands so Console can render structured --help output.
 *
 * Usage:
 *   protected function define(): CommandDefinition
 *   {
 *       return (new CommandDefinition())
 *           ->argument('name', 'The user name')
 *           ->option('force', 'f', 'Skip confirmation');
 *   }
 *
 * @package EzPhp\Console
 */
final class CommandDefinition
{
    /**
     * @var list<ArgumentDefinition>
     */
    private array $arguments = [];

    /**
     * @var list<OptionDefinition>
     */
    private array $options = [];

    /**
     * Add a positional argument to the definition.
     *
     * @param string $name        Argument name shown in help output.
     * @param string $description One-line description.
     * @param bool   $required    Whether the argument is required (default: true).
     *
     * @return $this
     */
    public function argument(string $name, string $description, bool $required = true): self
    {
        $this->arguments[] = new ArgumentDefinition($name, $description, $required);

        return $this;
    }

    /**
     * Add a named option to the definition.
     *
     * @param string $name        Long option name (without dashes), e.g. 'force'.
     * @param string $short       Short single-char alias (without dash), e.g. 'f'. Empty = no alias.
     * @param string $description One-line description.
     *
     * @return $this
     */
    public function option(string $name, string $short = '', string $description = ''): self
    {
        $this->options[] = new OptionDefinition($name, $short, $description);

        return $this;
    }

    /**
     * Return all registered argument definitions.
     *
     * @return list<ArgumentDefinition>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Return all registered option definitions.
     *
     * @return list<OptionDefinition>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
