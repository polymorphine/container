<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Setup;

use PHPUnit\Framework\TestCase;
use Polymorphine\Container\Records;
use Polymorphine\Container\Setup;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;
use Polymorphine\Container\Tests\Doubles;


class BuildTest extends TestCase
{
    public function testBuild_container_ReturnsRecordContainerWithDefinedRecords()
    {
        $setup = $this->builder($records = ['foo' => Doubles\MockedRecord::new()]);
        $this->assertEquals(new RecordContainer($this->records($records)), $setup->container());
    }

    public function testBuildWithSubContainers_container_ReturnsCompositeContainer()
    {
        $setup = $this->builder(
            $records = ['foo' => Doubles\MockedRecord::new()],
            $containers = ['bar' => Doubles\FakeContainer::new()]
        );
        $this->assertEquals(new CompositeContainer($this->records($records), $containers), $setup->container());
    }

    public function testBuild_addRecord_WillCreateContainerWithAddedRecords()
    {
        $setup = $this->builder();
        $setup->setRecord('foo', $added = Doubles\MockedRecord::new('added'));
        $this->assertSame('added', $setup->container()->get('foo'));
    }

    public function testBuild_addContainer_WillCreateContainerWithAddedContainers()
    {
        $setup = $this->builder();
        $setup->setContainer('foo', $container = Doubles\FakeContainer::new());
        $this->assertSame($container, $setup->container()->get('foo'));
    }

    public function testBuild_has_ReturnsTrueForDefinedIds()
    {
        $setup = $this->builder(
            ['record' => Doubles\MockedRecord::new()],
            ['container' => Doubles\FakeContainer::new()]
        );
        $setup->setRecord('addedRecord', Doubles\MockedRecord::new());
        $setup->setContainer('addedContainer', Doubles\FakeContainer::new());

        $this->assertTrue($setup->has('record'));
        $this->assertTrue($setup->has('container'));
        $this->assertTrue($setup->has('addedRecord'));
        $this->assertTrue($setup->has('addedContainer'));

        $this->assertFalse($setup->has('undefined'));
        $this->assertFalse($setup->has('Record'));
    }

    protected function builder(array $records = [], array $containers = []): Setup\Build
    {
        return new Setup\Build($records, $containers);
    }

    protected function records(array $records = []): Records
    {
        return new Records($records);
    }
}
