<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class ArgumentDefinition
 *
 * Describes a single positional argument for a console command.
 *
 * @package EzPhp\Console
 */
final readonly class ArgumentDefinition
{
    /**
     * ArgumentDefinition Constructor
     *
     * @param string $name        Argument name shown in help output (e.g. 'name', 'path').
     * @param string $description One-line description shown in help output.
     * @param bool   $required    Whether the argument is required (default: true).
     */
    public function __construct(
        public string $name,
        public string $description,
        public bool $required = true,
    ) {
    }
}
