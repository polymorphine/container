<?php declare(strict_types=1);

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
    public function test_Instantiation()
    {
        $this->assertInstanceOf(Setup::class, $this->builder());
        $this->assertInstanceOf(Setup::class, Setup::production());
        $this->assertInstanceOf(Setup::class, Setup::development());
    }

    public function test_Container_ReturnsContainerFromBuild()
    {
        $setup = $this->builder($build);
        $this->assertSame($setup->container(), $build->container);
    }

    public function test_Set_ForUndefinedId_ReturnsEntryObject()
    {
        $build = Doubles\MockedBuild::undefined();
        $this->assertEquals(new Setup\Entry('foo', $build), $this->builder($build)->set('foo'));
    }

    public function test_Set_ForDefinedId_ThrowsException()
    {
        $setup = new Setup(Doubles\MockedBuild::defined());
        $this->expectException(Setup\Exception\OverwriteRuleException::class);
        $setup->set('foo');
    }

    private function builder(?Doubles\MockedBuild &$build = null): Setup
    {
        return new Setup($build ??= new Doubles\MockedBuild());
    }
}
