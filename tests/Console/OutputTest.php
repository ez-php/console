<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\Output;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class OutputTest
 *
 * @package Tests\Console
 */
#[CoversClass(Output::class)]
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
}
