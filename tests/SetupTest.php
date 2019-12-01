<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests;

use PHPUnit\Framework\TestCase;
use Polymorphine\Container\Setup;


class SetupTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Setup::class, $this->builder());
        $this->assertInstanceOf(Setup::class, Setup::basic());
        $this->assertInstanceOf(Setup::class, Setup::validated());
    }

    public function testSetup_container_ReturnsContainerFromBuild()
    {
        $setup = $this->builder($build);
        $this->assertSame($setup->container(), $build->container);
    }

    public function testSetup_add_ReturnsAddEntryObject()
    {
        $setup    = $this->builder($build);
        $expected = new Setup\Entry\AddEntry('foo', $build);
        $this->assertEquals($expected, $setup->add('foo'));
    }

    public function testSetup_replace_ReturnsReplaceEntryObject()
    {
        $setup    = $this->builder($build);
        $expected = new Setup\Entry\ReplaceEntry('foo', $build);
        $this->assertEquals($expected, $setup->replace('foo'));
    }

    public function testSetup_decorate_ReturnsReplacingWrapper()
    {
        $setup = $this->builder($build);
        $this->assertEquals($setup->decorate('foo'), $build->wrapper);
    }

    private function builder(?Setup\Build &$build = null): Setup
    {
        $build = new Doubles\MockedBuild();
        return new Setup($build);
    }
}
