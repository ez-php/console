<?php

declare(strict_types=1);

/**
 * Performance benchmark for EzPhp\Console\Console.
 *
 * Measures the overhead of registering commands and dispatching
 * a known command through the Console dispatcher.
 *
 * Exits with code 1 if the per-dispatch time exceeds the defined threshold,
 * allowing CI to detect performance regressions automatically.
 *
 * Usage:
 *   php benchmarks/dispatch.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use EzPhp\Console\CommandInterface;
use EzPhp\Console\Console;

const ITERATIONS = 2000;
const THRESHOLD_MS = 2.0; // per-dispatch upper bound in milliseconds

// ── Minimal no-op command ────────────────────────────────────────────────────

final class BenchCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'bench:noop';
    }

    public function getDescription(): string
    {
        return 'No-op benchmark command';
    }

    public function getHelp(): string
    {
        return '';
    }

    /** @param list<string> $args */
    public function handle(array $args): int
    {
        return 0;
    }
}

// ── Benchmark ─────────────────────────────────────────────────────────────────

$console = new Console([new BenchCommand()]);

// Warm-up
ob_start();
$console->run(['ez', 'bench:noop']);
ob_end_clean();

$start = hrtime(true);

for ($i = 0; $i < ITERATIONS; $i++) {
    ob_start();
    $console->run(['ez', 'bench:noop']);
    ob_end_clean();
}

$end = hrtime(true);

$totalMs = ($end - $start) / 1_000_000;
$perDispatch = $totalMs / ITERATIONS;

echo sprintf(
    "Console Dispatch Benchmark\n" .
    "  Commands registered  : 1\n" .
    "  Iterations           : %d\n" .
    "  Total time           : %.2f ms\n" .
    "  Per dispatch         : %.3f ms\n" .
    "  Threshold            : %.1f ms\n",
    ITERATIONS,
    $totalMs,
    $perDispatch,
    THRESHOLD_MS,
);

if ($perDispatch > THRESHOLD_MS) {
    echo sprintf(
        "FAIL: %.3f ms exceeds threshold of %.1f ms\n",
        $perDispatch,
        THRESHOLD_MS,
    );
    exit(1);
}

echo "PASS\n";
exit(0);
