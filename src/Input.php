<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class Input
 *
 * Parses a raw argv list into positional arguments and named options/flags.
 *
 *   Positional:  "foo"
 *   Option:      "--name=value"  → option('name') === 'value'
 *   Flag:        "--verbose"     → hasFlag('verbose') === true
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
}
