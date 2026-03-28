<?php

declare(strict_types=1);

namespace EzPhp\Console;

use InvalidArgumentException;

/**
 * Class Prompt
 *
 * Interactive terminal prompts: free text, yes/no confirmation, and multiple choice.
 *
 * Usage:
 *   $prompt = new Prompt();
 *   $name   = $prompt->ask('What is your name?');
 *   $ok     = $prompt->confirm('Continue?');
 *   $color  = $prompt->choice('Pick a color', ['red', 'green', 'blue']);
 *
 * Inject a custom InputStreamInterface for testing:
 *   $prompt = new Prompt(new MemoryInputStream(['Alice', 'y', '1']));
 *
 * @package EzPhp\Console
 */
final readonly class Prompt
{
    /**
     * Prompt Constructor
     *
     * @param InputStreamInterface $input Input stream (defaults to STDIN via StdinInputStream).
     */
    public function __construct(
        private InputStreamInterface $input = new StdinInputStream(),
    ) {
    }

    /**
     * Prompt the user for a free-text answer.
     *
     * @param string $question The question to display.
     *
     * @return string The trimmed user input.
     */
    public function ask(string $question): string
    {
        echo $question . ' ';

        return trim($this->input->readLine());
    }

    /**
     * Prompt the user for a yes/no confirmation.
     *
     * Accepts 'y' or 'yes' (case-insensitive) as true; everything else is false.
     *
     * @param string $question The question to display (a '[y/N]' hint is appended).
     *
     * @return bool
     */
    public function confirm(string $question): bool
    {
        echo $question . ' [y/N] ';

        $answer = strtolower(trim($this->input->readLine()));

        return $answer === 'y' || $answer === 'yes';
    }

    /**
     * Prompt the user to pick one option from a numbered list.
     *
     * @param string       $question The question to display.
     * @param list<string> $options  Available options (displayed as a numbered list starting at 0).
     *
     * @return string The selected option value.
     *
     * @throws InvalidArgumentException When the user enters an invalid index.
     */
    public function choice(string $question, array $options): string
    {
        echo $question . "\n";

        foreach ($options as $index => $option) {
            echo '  [' . $index . '] ' . $option . "\n";
        }

        echo 'Your choice: ';

        $answer = trim($this->input->readLine());
        $index = (int) $answer;

        if (!array_key_exists($index, $options)) {
            throw new InvalidArgumentException('Invalid choice: ' . $answer);
        }

        return $options[$index];
    }
}
