<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Interface CommandInterface
 *
 * @package EzPhp\Console
 */
interface CommandInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * Return extended help text shown when --help is passed.
     * Return an empty string to fall back to the description only.
     *
     * @return string
     */
    public function getHelp(): string;

    /**
     * @param list<string> $args
     *
     * @return int
     */
    public function handle(array $args): int;
}
