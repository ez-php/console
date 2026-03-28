<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Interface HasDefinition
 *
 * Optional extension for commands that expose a structured CommandDefinition.
 * When Console detects that a command implements this interface it uses the
 * definition to render arguments and options sections in the --help output,
 * in addition to the free-text returned by getHelp().
 *
 * @package EzPhp\Console
 */
interface HasDefinition
{
    /**
     * Return the command definition describing its arguments and options.
     *
     * @return CommandDefinition
     */
    public function getDefinition(): CommandDefinition;
}
