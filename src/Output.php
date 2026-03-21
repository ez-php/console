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

    /**
     * Render an ASCII table with headers and rows to stdout.
     *
     * Each row must contain the same number of cells as $headers.
     * Extra cells are ignored; missing cells are treated as empty strings.
     *
     * @param list<string>       $headers Column headers.
     * @param list<list<string>> $rows    Table rows.
     *
     * @return void
     */
    public static function table(array $headers, array $rows): void
    {
        if ($headers === []) {
            return;
        }

        $colCount = count($headers);

        /** @var array<int, int> $widths */
        $widths = [];
        for ($i = 0; $i < $colCount; $i++) {
            $widths[$i] = strlen($headers[$i]);
        }

        foreach ($rows as $row) {
            for ($i = 0; $i < $colCount; $i++) {
                $cell = array_key_exists($i, $row) ? $row[$i] : '';
                if (strlen($cell) > $widths[$i]) {
                    $widths[$i] = strlen($cell);
                }
            }
        }

        $border = '+' . implode('+', array_map(
            fn (int $w): string => str_repeat('-', $w + 2),
            $widths,
        )) . '+';

        echo $border . "\n";

        $headerRow = '|';
        for ($i = 0; $i < $colCount; $i++) {
            $headerRow .= ' ' . str_pad($headers[$i], $widths[$i]) . ' |';
        }
        echo $headerRow . "\n";
        echo $border . "\n";

        foreach ($rows as $row) {
            $line = '|';
            for ($i = 0; $i < $colCount; $i++) {
                $cell = array_key_exists($i, $row) ? $row[$i] : '';
                $line .= ' ' . str_pad($cell, $widths[$i]) . ' |';
            }
            echo $line . "\n";
        }

        echo $border . "\n";
    }

    /**
     * Create a new ProgressBar instance for the given total.
     *
     * @param int $total Total number of steps.
     * @param int $width Width of the bar in characters (default: 40).
     *
     * @return ProgressBar
     */
    public static function progressBar(int $total, int $width = 40): ProgressBar
    {
        return new ProgressBar($total, $width);
    }
}
