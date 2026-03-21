<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\InputStreamInterface;
use EzPhp\Console\Prompt;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class PromptTest
 *
 * @package Tests\Console
 */
#[CoversClass(Prompt::class)]
final class PromptTest extends TestCase
{
    /**
     * Build a Prompt backed by a list of pre-defined lines.
     *
     * @param list<string> $lines
     *
     * @return Prompt
     */
    private function makePrompt(array $lines): Prompt
    {
        $stream = new class ($lines) implements InputStreamInterface {
            private int $pos = 0;

            /** @param list<string> $lines */
            public function __construct(private array $lines)
            {
            }

            public function readLine(): string
            {
                return $this->lines[$this->pos++] ?? '';
            }
        };

        return new Prompt($stream);
    }

    // ── ask ───────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_ask_displays_question_and_returns_trimmed_answer(): void
    {
        $prompt = $this->makePrompt(["Alice\n"]);

        ob_start();
        $answer = $prompt->ask('What is your name?');
        ob_get_clean();

        $this->assertSame('Alice', $answer);
    }

    /**
     * @return void
     */
    public function test_ask_trims_whitespace(): void
    {
        $prompt = $this->makePrompt(["  Bob  \n"]);

        ob_start();
        $answer = $prompt->ask('Name?');
        ob_get_clean();

        $this->assertSame('Bob', $answer);
    }

    // ── confirm ───────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_confirm_returns_true_for_y(): void
    {
        $prompt = $this->makePrompt(["y\n"]);

        ob_start();
        $result = $prompt->confirm('Continue?');
        ob_get_clean();

        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_confirm_returns_true_for_yes(): void
    {
        $prompt = $this->makePrompt(["yes\n"]);

        ob_start();
        $result = $prompt->confirm('Continue?');
        ob_get_clean();

        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_confirm_returns_true_for_uppercase_y(): void
    {
        $prompt = $this->makePrompt(["Y\n"]);

        ob_start();
        $result = $prompt->confirm('Continue?');
        ob_get_clean();

        $this->assertTrue($result);
    }

    /**
     * @return void
     */
    public function test_confirm_returns_false_for_n(): void
    {
        $prompt = $this->makePrompt(["n\n"]);

        ob_start();
        $result = $prompt->confirm('Continue?');
        ob_get_clean();

        $this->assertFalse($result);
    }

    /**
     * @return void
     */
    public function test_confirm_returns_false_for_empty_input(): void
    {
        $prompt = $this->makePrompt(["\n"]);

        ob_start();
        $result = $prompt->confirm('Continue?');
        ob_get_clean();

        $this->assertFalse($result);
    }

    // ── choice ────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_choice_returns_selected_option(): void
    {
        $prompt = $this->makePrompt(["1\n"]);

        ob_start();
        $result = $prompt->choice('Pick a color', ['red', 'green', 'blue']);
        ob_get_clean();

        $this->assertSame('green', $result);
    }

    /**
     * @return void
     */
    public function test_choice_returns_first_option_for_index_zero(): void
    {
        $prompt = $this->makePrompt(["0\n"]);

        ob_start();
        $result = $prompt->choice('Pick', ['alpha', 'beta']);
        ob_get_clean();

        $this->assertSame('alpha', $result);
    }

    /**
     * @return void
     */
    public function test_choice_throws_for_invalid_index(): void
    {
        $prompt = $this->makePrompt(["99\n"]);

        ob_start();
        try {
            $prompt->choice('Pick', ['one', 'two']);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('99', $e->getMessage());
        } finally {
            ob_get_clean();
        }
    }

    /**
     * @return void
     */
    public function test_choice_displays_question_and_options(): void
    {
        $prompt = $this->makePrompt(["0\n"]);

        ob_start();
        $prompt->choice('Choose', ['option-a', 'option-b']);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('Choose', $out);
        $this->assertStringContainsString('option-a', $out);
        $this->assertStringContainsString('option-b', $out);
    }
}
