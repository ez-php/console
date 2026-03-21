<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Interface InputStreamInterface
 *
 * Abstraction over a readable character stream, used by Prompt to read user input.
 * The default implementation reads from STDIN; tests inject a MemoryInputStream.
 *
 * @package EzPhp\Console
 */
interface InputStreamInterface
{
    /**
     * Read one line from the stream and return it (including the trailing newline, if any).
     * Returns an empty string when the stream is exhausted.
     *
     * @return string
     */
    public function readLine(): string;
}
