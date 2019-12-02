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
        $records = ['foo' => Doubles\MockedRecord::new()];
        $setup   = $this->builder($records);
        $this->assertEquals(new RecordContainer($this->records($records)), $setup->container());
    }

    public function testBuildWithSubContainers_container_ReturnsCompositeContainer()
    {
        $records    = ['foo' => Doubles\MockedRecord::new()];
        $containers = ['bar' => Doubles\FakeContainer::new()];
        $setup      = $this->builder($records, $containers);
        $this->assertEquals(new CompositeContainer($this->records($records), $containers), $setup->container());
    }

    public function testBuild_addRecord_WillCreateContainerWithAddedRecords()
    {
        $setup = $this->builder();
        $setup->addRecord('foo', $added = Doubles\MockedRecord::new('added'));
        $this->assertSame('added', $setup->container()->get('foo'));
    }

    public function testBuild_replaceRecord_WillCreateContainerWithReplacedRecords()
    {
        $setup = $this->builder(['foo' => Doubles\MockedRecord::new('original')]);
        $setup->replaceRecord('foo', $replaced = Doubles\MockedRecord::new('replaced'));
        $this->assertSame('replaced', $setup->container()->get('foo'));
    }

    public function testBuild_addContainer_WillCreateContainerWithAddedContainers()
    {
        $setup = $this->builder();
        $setup->addContainer('foo', $container = Doubles\FakeContainer::new());
        $this->assertSame($container, $setup->container()->get('foo'));
    }

    public function testBuild_replaceContainer_WillCreateContainerWithReplacedContainers()
    {
        $setup = $this->builder([], ['foo' => Doubles\FakeContainer::new()]);
        $setup->replaceContainer('foo', $replaced = Doubles\FakeContainer::new());
        $this->assertSame($replaced, $setup->container()->get('foo'));
    }

    public function testBuild_decoratorWithRecordId_ReturnsWrapperForGivenRecord()
    {
        $setup    = $this->builder(['foo' => $record = Doubles\MockedRecord::new('not decorated')]);
        $expected = new Setup\Entry\Wrapper('foo', $record, new Setup\Entry\ReplaceEntry('foo', $setup));
        $this->assertEquals($expected, $setup->decorator('foo'));
    }

    public function testBuild_decoratorForUndefinedRecord_ThrowsException()
    {
        $setup = $this->builder(['foo' => Doubles\MockedRecord::new('not decorated')]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->decorator('bar');
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
