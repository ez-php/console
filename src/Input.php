<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class Input
 *
 * Parses a raw argv list into positional arguments, long options/flags, and short options/flags.
 *
 *   Positional:        "foo"
 *   Long option:       "--name=value"  → option('name') === 'value'
 *   Long flag:         "--verbose"     → hasFlag('verbose') === true
 *   Short flag:        "-v"            → hasShortFlag('v') === true
 *   Combined flags:    "-vf"           → hasShortFlag('v') && hasShortFlag('f')
 *   Short option:      "-n=value"      → shortOption('n') === 'value'
 *
 * Note: space-separated short options ("-n value") are intentionally not supported
 * because they are ambiguous without a declaration schema.
 *
 * @package EzPhp\Console
 */
final class Input
{
    /**
     * @var list<string>
     */
    private array $arguments = [];

    /**
     * @var array<string, string|true>
     */
    private array $options = [];

    /**
     * @var array<string, string|true>
     */
    private array $shortOptions = [];

    /**
     * Input Constructor
     *
     * @param list<string> $raw Raw argv tokens (after the command name).
     */
    public function __construct(array $raw)
    {
        foreach ($raw as $token) {
            if (str_starts_with($token, '--')) {
                $token = substr($token, 2);

                if (str_contains($token, '=')) {
                    [$name, $value] = explode('=', $token, 2);
                    $this->options[$name] = $value;
                } else {
                    $this->options[$token] = true;
                }
            } elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                $rest = substr($token, 1);

                if (str_contains($rest, '=')) {
                    [$key, $value] = explode('=', $rest, 2);
                    $this->shortOptions[$key] = $value;
                } else {
                    foreach (str_split($rest) as $char) {
                        $this->shortOptions[$char] = true;
                    }
                }
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    /**
     * Return the positional argument at the given zero-based index, or null.
     *
     * @param int $index
     *
     * @return string|null
     */
    public function argument(int $index): ?string
    {
        return $this->arguments[$index] ?? null;
    }

    /**
     * Return all positional arguments.
     *
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * Return the value of a named option (--name=value).
     * Returns an empty string if the option was given as a flag (--name).
     * Returns $default if the option is absent.
     *
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function option(string $name, string $default = ''): string
    {
        $value = $this->options[$name] ?? null;

        if ($value === null) {
            return $default;
        }

        return $value === true ? '' : $value;
    }

    /**
     * Return true if --name or --name=value was present.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasFlag(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Return true if -x or -x=value was present (including combined flags like -vf).
     *
     * @param string $char
     *
     * @return bool
     */
    public function hasShortFlag(string $char): bool
    {
        return isset($this->shortOptions[$char]);
    }

    /**
     * Return the value of a short option (-x=value).
     * Returns an empty string if the flag was given without a value (-x).
     * Returns $default if the short option is absent.
     *
     * @param string $char
     * @param string $default
     *
     * @return string
     */
    public function shortOption(string $char, string $default = ''): string
    {
        $value = $this->shortOptions[$char] ?? null;

        if ($value === null) {
            return $default;
        }

        return $value === true ? '' : $value;
    }
}
