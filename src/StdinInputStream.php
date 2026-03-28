<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class StdinInputStream
 *
 * Reads lines from STDIN. Used as the default input stream in Prompt.
 *
 * @package EzPhp\Console
 */
final class StdinInputStream implements InputStreamInterface
{
    /**
     * Read one line from STDIN.
     *
     * @return string
     */
    public function readLine(): string
    {
        $line = fgets(STDIN);

        return $line !== false ? $line : '';
    }
}
