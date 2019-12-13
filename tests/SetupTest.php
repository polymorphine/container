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
        $this->assertInstanceOf(Setup::class, Setup::production());
        $this->assertInstanceOf(Setup::class, Setup::development());
    }

    public function testSetup_container_ReturnsContainerFromBuild()
    {
        $setup = $this->builder($build);
        $this->assertSame($setup->container(), $build->container);
    }

    public function testSetup_setUndefinedId_ReturnsEntryObject()
    {
        $setup    = new Setup($build = Doubles\MockedBuild::undefined());
        $expected = new Setup\Entry('foo', $build);
        $this->assertEquals($expected, $setup->set('foo'));
    }

    public function testSetup_setDefinedId_ThrowsException()
    {
        $setup = new Setup(Doubles\MockedBuild::defined());
        $this->expectException(Setup\Exception\OverwriteRuleException::class);
        $setup->set('foo');
    }

    public function testSetup_replaceDefinedId_ReturnsEntryObject()
    {
        $setup    = new Setup($build = Doubles\MockedBuild::defined());
        $expected = new Setup\Entry('foo', $build);
        $this->assertEquals($expected, $setup->replace('foo'));
    }

    public function testSetup_replaceUndefinedId_ThrowsException()
    {
        $setup = new Setup(Doubles\MockedBuild::undefined());
        $this->expectException(Setup\Exception\OverwriteRuleException::class);
        $setup->replace('foo');
    }

    public function testSetup_fallbackForDefinedId_ReturnsInactiveEntryObject()
    {
        $setup    = new Setup($build = Doubles\MockedBuild::defined());
        $expected = new Setup\Entry('foo', $build);
        $this->assertNotEquals($expected, $entry = $setup->fallback('foo'));
        $this->assertInstanceOf(Setup\Entry::class, $entry);
    }

    public function testSetup_fallbackForUndefinedId_ReturnsEntryObject()
    {
        $setup    = new Setup($build = Doubles\MockedBuild::undefined());
        $expected = new Setup\Entry('foo', $build);
        $this->assertEquals($expected, $setup->fallback('foo'));
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
