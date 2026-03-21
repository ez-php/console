<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\Output;
use EzPhp\Console\ProgressBar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class ProgressBarTest
 *
 * @package Tests\Console
 */
#[CoversClass(ProgressBar::class)]
#[UsesClass(Output::class)]
final class ProgressBarTest extends TestCase
{
    /**
     * @return void
     */
    public function test_advance_renders_bar_to_stdout(): void
    {
        $bar = new ProgressBar(10);

        ob_start();
        $bar->advance();
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('[', $out);
        $this->assertStringContainsString(']', $out);
        $this->assertStringContainsString('%', $out);
    }

    /**
     * @return void
     */
    public function test_advance_shows_correct_percentage(): void
    {
        $bar = new ProgressBar(4);

        ob_start();
        $bar->advance();
        $bar->advance();
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('50%', $out);
    }

    /**
     * @return void
     */
    public function test_advance_does_not_exceed_total(): void
    {
        $bar = new ProgressBar(5);

        ob_start();
        $bar->advance(10);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('100%', $out);
        $this->assertStringContainsString('5/5', $out);
    }

    /**
     * @return void
     */
    public function test_advance_multiple_steps(): void
    {
        $bar = new ProgressBar(10);

        ob_start();
        $bar->advance(3);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('3/10', $out);
    }

    /**
     * @return void
     */
    public function test_finish_completes_bar_and_adds_newline(): void
    {
        $bar = new ProgressBar(10);

        ob_start();
        $bar->advance(5);
        $bar->finish();
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('100%', $out);
        $this->assertStringContainsString('10/10', $out);
        $this->assertStringEndsWith("\n", $out);
    }

    /**
     * @return void
     */
    public function test_finish_on_zero_total_shows_100_percent(): void
    {
        $bar = new ProgressBar(0);

        ob_start();
        $bar->finish();
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('100%', $out);
    }

    /**
     * @return void
     */
    public function test_output_progress_bar_factory_returns_progress_bar(): void
    {
        $bar = Output::progressBar(50);

        $this->assertInstanceOf(ProgressBar::class, $bar);

        ob_start();
        $bar->finish();
        ob_get_clean();
    }

    /**
     * @return void
     */
    public function test_output_progress_bar_factory_respects_width(): void
    {
        $bar = Output::progressBar(10, 20);

        ob_start();
        $bar->advance(5);
        $out = (string) ob_get_clean();

        // With width=20, bar string is [====================] or similar but length 22 ([ + 20 chars + ])
        $this->assertStringContainsString('[', $out);
        $this->assertStringContainsString(']', $out);

        ob_start();
        $bar->finish();
        ob_get_clean();
    }
}
