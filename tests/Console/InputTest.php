<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Class InputTest
 *
 * @package Tests\Console
 */
#[CoversClass(Input::class)]
final class InputTest extends TestCase
{
    /**
     * @return void
     */
    public function test_positional_arguments_are_parsed(): void
    {
        $input = new Input(['foo', 'bar', 'baz']);

        $this->assertSame('foo', $input->argument(0));
        $this->assertSame('bar', $input->argument(1));
        $this->assertSame('baz', $input->argument(2));
        $this->assertNull($input->argument(3));
    }

    /**
     * @return void
     */
    public function test_arguments_returns_all_positional(): void
    {
        $input = new Input(['foo', 'bar']);

        $this->assertSame(['foo', 'bar'], $input->arguments());
    }

    /**
     * @return void
     */
    public function test_options_with_value_are_parsed(): void
    {
        $input = new Input(['--name=Alice', '--count=3']);

        $this->assertSame('Alice', $input->option('name'));
        $this->assertSame('3', $input->option('count'));
    }

    /**
     * @return void
     */
    public function test_option_returns_default_when_absent(): void
    {
        $input = new Input([]);

        $this->assertSame('default', $input->option('missing', 'default'));
        $this->assertSame('', $input->option('missing'));
    }

    /**
     * @return void
     */
    public function test_flag_only_option_returns_empty_string(): void
    {
        $input = new Input(['--verbose']);

        $this->assertSame('', $input->option('verbose'));
    }

    /**
     * @return void
     */
    public function test_has_flag_returns_true_when_present(): void
    {
        $input = new Input(['--verbose', '--force']);

        $this->assertTrue($input->hasFlag('verbose'));
        $this->assertTrue($input->hasFlag('force'));
    }

    /**
     * @return void
     */
    public function test_has_flag_returns_true_for_option_with_value(): void
    {
        $input = new Input(['--name=Alice']);

        $this->assertTrue($input->hasFlag('name'));
    }

    /**
     * @return void
     */
    public function test_has_flag_returns_false_when_absent(): void
    {
        $input = new Input([]);

        $this->assertFalse($input->hasFlag('missing'));
    }

    /**
     * @return void
     */
    public function test_options_are_not_included_in_arguments(): void
    {
        $input = new Input(['foo', '--verbose', 'bar', '--name=test']);

        $this->assertSame(['foo', 'bar'], $input->arguments());
    }

    /**
     * @return void
     */
    public function test_empty_input_returns_nulls_and_defaults(): void
    {
        $input = new Input([]);

        $this->assertNull($input->argument(0));
        $this->assertSame([], $input->arguments());
        $this->assertSame('', $input->option('anything'));
        $this->assertFalse($input->hasFlag('anything'));
    }
}
