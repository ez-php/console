<?php

declare(strict_types=1);

namespace Tests;

use EzPhp\Console\OptionDefinition;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class OptionDefinitionTest
 *
 * @package Tests
 */
#[CoversClass(OptionDefinition::class)]
final class OptionDefinitionTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $option = new OptionDefinition('force', 'f', 'Force the operation');

        $this->assertSame('force', $option->name);
        $this->assertSame('f', $option->short);
        $this->assertSame('Force the operation', $option->description);
    }

    public function test_short_and_description_default_to_empty_string(): void
    {
        $option = new OptionDefinition('verbose');

        $this->assertSame('', $option->short);
        $this->assertSame('', $option->description);
    }
}
