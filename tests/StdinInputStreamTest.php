<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Console\StdinInputStream;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class StdinInputStreamTest
 *
 * StdinInputStream::readLine() reads the literal STDIN constant, which is
 * bound to the running process's real standard input and cannot be swapped
 * out in-process (unlike the scripted anonymous InputStreamInterface used
 * everywhere else Prompt is tested). The only way to genuinely exercise it is to run it in a
 * subprocess with a piped stdin, via proc_open().
 *
 * @package Tests
 */
#[CoversClass(StdinInputStream::class)]
final class StdinInputStreamTest extends TestCase
{
    /**
     * Run a tiny PHP script in a subprocess that constructs a
     * StdinInputStream and calls readLine() a fixed number of times,
     * printing each result separated by a NUL byte (so we can tell an
     * empty-line result apart from end-of-output).
     *
     * @param string $stdinContent Bytes fed to the subprocess's stdin.
     * @param int    $reads        Number of readLine() calls to perform.
     *
     * @return list<string> One entry per readLine() call.
     */
    private function readLinesInSubprocess(string $stdinContent, int $reads): array
    {
        $autoloader = $this->findAutoloader();

        $script = sprintf(
            'require %s; $s = new \\EzPhp\\Console\\StdinInputStream();'
                . 'for ($i = 0; $i < %d; $i++) { echo $s->readLine(); echo "\\0"; }',
            var_export($autoloader, true),
            $reads,
        );

        $process = proc_open(
            ['php', '-r', $script],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        self::assertIsResource($process, 'failed to start PHP subprocess');

        fwrite($pipes[0], $stdinContent);
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        self::assertIsString($stdout);

        $parts = explode("\0", $stdout);
        // The trailing empty string after the final separator is not a result.
        array_pop($parts);

        return $parts;
    }

    /**
     * Locates a Composer autoloader usable by the subprocess.
     *
     * This module's own vendor/autoload.php only exists when composer
     * install has run inside modules/console/ directly (standalone module
     * CI, local module dev). In the monorepo's aggregated run, only the
     * repository-root vendor/ is installed — modules/console/vendor/ is
     * gitignored and absent from a fresh checkout — so fall back to the
     * nearest ancestor vendor/autoload.php, matching how the parent
     * PHPUnit process itself is bootstrapped.
     *
     * @return string
     */
    private function findAutoloader(): string
    {
        $moduleAutoloader = dirname(__DIR__) . '/vendor/autoload.php';
        if (is_file($moduleAutoloader)) {
            return $moduleAutoloader;
        }

        $dir = dirname(__DIR__);
        while (true) {
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
            $candidate = $dir . '/vendor/autoload.php';
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        self::fail('Could not locate a Composer autoloader for the subprocess.');
    }

    public function test_reads_single_line_with_trailing_newline(): void
    {
        $lines = $this->readLinesInSubprocess("hello\n", 1);

        $this->assertSame(["hello\n"], $lines);
    }

    public function test_reads_multiple_lines_in_order(): void
    {
        $lines = $this->readLinesInSubprocess("first\nsecond\nthird\n", 3);

        $this->assertSame(["first\n", "second\n", "third\n"], $lines);
    }

    public function test_returns_empty_string_when_stream_is_exhausted(): void
    {
        // Only one line is provided, but three reads are requested — the
        // second and third must fall back to '' (fgets() returning false).
        $lines = $this->readLinesInSubprocess("only\n", 3);

        $this->assertSame(["only\n", '', ''], $lines);
    }

    public function test_reads_final_line_without_trailing_newline(): void
    {
        // No trailing \n on the last line, and stdin closes immediately after.
        $lines = $this->readLinesInSubprocess('no newline at end', 1);

        $this->assertSame(['no newline at end'], $lines);
    }
}
