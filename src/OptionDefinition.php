<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class OptionDefinition
 *
 * Describes a single named option (--name / -s) for a console command.
 *
 * @package EzPhp\Console
 */
final readonly class OptionDefinition
{
    /**
     * OptionDefinition Constructor
     *
     * @param string $name        Long option name without leading dashes (e.g. 'force').
     * @param string $short       Single-character short alias without leading dash (e.g. 'f'). Empty = no alias.
     * @param string $description One-line description shown in help output.
     */
    public function __construct(
        public string $name,
        public string $short = '',
        public string $description = '',
    ) {
    }
}
