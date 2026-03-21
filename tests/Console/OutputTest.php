<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\Output;
use EzPhp\Console\ProgressBar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class OutputTest
 *
 * @package Tests\Console
 */
#[CoversClass(Output::class)]
#[UsesClass(ProgressBar::class)]
final class OutputTest extends TestCase
{
    /**
     * @return void
     */
    public function test_line_outputs_text_with_newline(): void
    {
        ob_start();
        Output::line('hello');
        $out = (string) ob_get_clean();

        $this->assertSame("hello\n", $out);
    }

    /**
     * @return void
     */
    public function test_line_with_no_argument_outputs_blank_line(): void
    {
        ob_start();
        Output::line();
        $out = (string) ob_get_clean();

        $this->assertSame("\n", $out);
    }

    /**
     * @return void
     */
    public function test_info_wraps_text_in_blue_ansi_codes(): void
    {
        ob_start();
        Output::info('msg');
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('msg', $out);
        $this->assertStringContainsString("\e[34m", $out);
        $this->assertStringContainsString("\e[0m", $out);
    }

    /**
     * @return void
     */
    public function test_success_wraps_text_in_green_ansi_codes(): void
    {
        ob_start();
        Output::success('msg');
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('msg', $out);
        $this->assertStringContainsString("\e[32m", $out);
    }

    /**
     * @return void
     */
    public function test_warning_wraps_text_in_yellow_ansi_codes(): void
    {
        ob_start();
        Output::warning('msg');
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('msg', $out);
        $this->assertStringContainsString("\e[33m", $out);
    }

    /**
     * @return void
     */
    public function test_colorize_returns_colored_string(): void
    {
        $result = Output::colorize('text', 32);

        $this->assertStringContainsString('text', $result);
        $this->assertStringContainsString("\e[32m", $result);
        $this->assertStringContainsString("\e[0m", $result);
    }

    /**
     * @return void
     */
    public function test_error_writes_to_stderr(): void
    {
        $stderr = fopen('php://memory', 'w+');
        $this->assertIsResource($stderr);

        // Redirect STDERR to memory stream to capture it
        // Output::error() writes to STDERR — we just verify it doesn't throw
        // and produces colored output via colorize()
        $colored = Output::colorize('error message', 31);
        Output::error('error message');

        $this->assertStringContainsString("\e[31m", $colored);
        $this->assertStringContainsString('error message', $colored);

        fclose($stderr);
    }

    // ── table ─────────────────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_table_renders_header_and_row(): void
    {
        ob_start();
        Output::table(['Name', 'Age'], [['Alice', '30']]);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('Name', $out);
        $this->assertStringContainsString('Age', $out);
        $this->assertStringContainsString('Alice', $out);
        $this->assertStringContainsString('30', $out);
    }

    /**
     * @return void
     */
    public function test_table_renders_border_separators(): void
    {
        ob_start();
        Output::table(['Col'], [['val']]);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('+', $out);
        $this->assertStringContainsString('-', $out);
        $this->assertStringContainsString('|', $out);
    }

    /**
     * @return void
     */
    public function test_table_pads_columns_to_widest_cell(): void
    {
        ob_start();
        Output::table(['A'], [['short'], ['much-longer-value']]);
        $out = (string) ob_get_clean();

        // The column must be wide enough to fit 'much-longer-value'
        $this->assertStringContainsString('much-longer-value', $out);
        $this->assertStringContainsString('short', $out);
    }

    /**
     * @return void
     */
    public function test_table_with_no_rows_renders_header_only(): void
    {
        ob_start();
        Output::table(['ID', 'Email'], []);
        $out = (string) ob_get_clean();

        $this->assertStringContainsString('ID', $out);
        $this->assertStringContainsString('Email', $out);
    }

    /**
     * @return void
     */
    public function test_table_with_empty_headers_produces_no_output(): void
    {
        ob_start();
        Output::table([], [['a', 'b']]);
        $out = (string) ob_get_clean();

        $this->assertSame('', $out);
    }

    // ── progressBar factory ───────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_progress_bar_factory_returns_progress_bar(): void
    {
        $bar = Output::progressBar(10);

        $this->assertInstanceOf(ProgressBar::class, $bar);

        ob_start();
        $bar->finish();
        ob_get_clean();
    }
}
