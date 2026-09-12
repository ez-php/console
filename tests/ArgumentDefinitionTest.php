<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Console\ArgumentDefinition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class ArgumentDefinitionTest
 *
 * @package Tests
 */
#[CoversClass(ArgumentDefinition::class)]
final class ArgumentDefinitionTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $arg = new ArgumentDefinition('name', 'The item name', false);

        $this->assertSame('name', $arg->name);
        $this->assertSame('The item name', $arg->description);
        $this->assertFalse($arg->required);
    }

    public function test_required_defaults_to_true(): void
    {
        $arg = new ArgumentDefinition('path', 'Target path');

        $this->assertTrue($arg->required);
    }
}
