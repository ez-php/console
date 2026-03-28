<?php

declare(strict_types=1);

namespace EzPhp\Console;

/**
 * Class ProgressBar
 *
 * Renders a terminal progress bar to stdout.
 *
 * Usage:
 *   $bar = Output::progressBar(100);
 *   foreach ($items as $item) {
 *       process($item);
 *       $bar->advance();
 *   }
 *   $bar->finish();
 *
 * @package EzPhp\Console
 */
final class ProgressBar
{
    private int $current = 0;

    /**
     * ProgressBar Constructor
     *
     * @param int $total Total number of steps.
     * @param int $width Width of the bar in characters.
     */
    public function __construct(
        private readonly int $total,
        private readonly int $width = 40,
    ) {
    }

    /**
     * Advance the progress bar by one or more steps and redraw it.
     *
     * @param int $step Number of steps to advance (default: 1).
     *
     * @return void
     */
    public function advance(int $step = 1): void
    {
        $this->current = min($this->current + $step, $this->total);
        $this->render();
    }

    /**
     * Mark the progress bar as complete and move to a new line.
     *
     * @return void
     */
    public function finish(): void
    {
        $this->current = $this->total;
        $this->render();
        echo "\n";
    }

    /**
     * Render the progress bar in-place using a carriage return.
     *
     * @return void
     */
    private function render(): void
    {
        $percent = $this->total > 0
            ? (int) round(($this->current / $this->total) * 100)
            : 100;

        $filled = $this->total > 0
            ? (int) floor(($this->current / $this->total) * $this->width)
            : $this->width;

        $empty = $this->width - $filled;
        $bar = '[' . str_repeat('=', $filled) . str_repeat(' ', $empty) . ']';

        echo "\r" . $bar . ' ' . $percent . '% (' . $this->current . '/' . $this->total . ')';
    }
}
