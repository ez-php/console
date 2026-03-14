<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class Output
 *
 * Helpers for colored ANSI terminal output.
 *
 * @package EzPhp\Console
 */
final class Output
{
    /**
     * Write a plain line to stdout.
     *
     * @param string $text
     *
     * @return void
     */
    public static function line(string $text = ''): void
    {
        echo $text . "\n";
    }

    /**
     * Write an informational (blue) line to stdout.
     *
     * @param string $text
     *
     * @return void
     */
    public static function info(string $text): void
    {
        echo "\e[34m$text\e[0m\n";
    }

    /**
     * Write a success (green) line to stdout.
     *
     * @param string $text
     *
     * @return void
     */
    public static function success(string $text): void
    {
        echo "\e[32m$text\e[0m\n";
    }

    /**
     * Write an error (red) line to stderr.
     *
     * @param string $text
     *
     * @return void
     */
    public static function error(string $text): void
    {
        fwrite(STDERR, "\e[31m$text\e[0m\n");
    }

    /**
     * Write a warning (yellow) line to stdout.
     *
     * @param string $text
     *
     * @return void
     */
    public static function warning(string $text): void
    {
        echo "\e[33m$text\e[0m\n";
    }

    /**
     * Wrap $text in the given ANSI color code and reset afterward.
     *
     * @param string $text
     * @param int    $code ANSI color code, e.g. 32 (green), 33 (yellow), 34 (blue).
     *
     * @return string
     */
    public static function colorize(string $text, int $code): string
    {
        return "\e[{$code}m$text\e[0m";
    }
}
