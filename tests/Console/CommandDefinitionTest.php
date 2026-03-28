<?php

declare(strict_types=1);

namespace Tests\Console;

use EzPhp\Console\ArgumentDefinition;
use EzPhp\Console\CommandDefinition;
use EzPhp\Console\OptionDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

/**
 * Class CommandDefinitionTest
 *
 * @package Tests\Console
 */
#[CoversClass(CommandDefinition::class)]
#[UsesClass(ArgumentDefinition::class)]
#[UsesClass(OptionDefinition::class)]
final class CommandDefinitionTest extends TestCase
{
    /**
     * @return void
     */
    public function test_argument_adds_argument_definition(): void
    {
        $def = (new CommandDefinition())->argument('name', 'The user name');

        $args = $def->getArguments();
        $this->assertCount(1, $args);
        $this->assertSame('name', $args[0]->name);
        $this->assertSame('The user name', $args[0]->description);
        $this->assertTrue($args[0]->required);
    }

    /**
     * @return void
     */
    public function test_argument_optional_flag(): void
    {
        $def = (new CommandDefinition())->argument('path', 'Target path', false);

        $this->assertFalse($def->getArguments()[0]->required);
    }

    /**
     * @return void
     */
    public function test_option_adds_option_definition(): void
    {
        $def = (new CommandDefinition())->option('force', 'f', 'Skip confirmation');

        $opts = $def->getOptions();
        $this->assertCount(1, $opts);
        $this->assertSame('force', $opts[0]->name);
        $this->assertSame('f', $opts[0]->short);
        $this->assertSame('Skip confirmation', $opts[0]->description);
    }

    /**
     * @return void
     */
    public function test_option_without_short(): void
    {
        $def = (new CommandDefinition())->option('verbose');

        $this->assertSame('', $def->getOptions()[0]->short);
    }

    /**
     * @return void
     */
    public function test_fluent_chaining_accumulates_entries(): void
    {
        $def = (new CommandDefinition())
            ->argument('name', 'Name')
            ->argument('email', 'Email', false)
            ->option('force', 'f', 'Force')
            ->option('quiet', 'q', 'Quiet');

        $this->assertCount(2, $def->getArguments());
        $this->assertCount(2, $def->getOptions());
    }

    /**
     * @return void
     */
    public function test_empty_definition_returns_empty_arrays(): void
    {
        $def = new CommandDefinition();

        $this->assertSame([], $def->getArguments());
        $this->assertSame([], $def->getOptions());
    }

    // ── ArgumentDefinition ────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_argument_definition_stores_properties(): void
    {
        $arg = new ArgumentDefinition('target', 'Target path', false);

        $this->assertSame('target', $arg->name);
        $this->assertSame('Target path', $arg->description);
        $this->assertFalse($arg->required);
    }

    // ── OptionDefinition ─────────────────────────────────────────────────────

    /**
     * @return void
     */
    public function test_option_definition_stores_properties(): void
    {
        $opt = new OptionDefinition('dry-run', 'd', 'Do not write anything');

        $this->assertSame('dry-run', $opt->name);
        $this->assertSame('d', $opt->short);
        $this->assertSame('Do not write anything', $opt->description);
    }

    /**
     * @return void
     */
    public function test_option_definition_defaults(): void
    {
        $opt = new OptionDefinition('verbose');

        $this->assertSame('verbose', $opt->name);
        $this->assertSame('', $opt->short);
        $this->assertSame('', $opt->description);
    }
}
