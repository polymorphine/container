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


abstract class BuildTest extends TestCase
{
    public function testSetup_container_ReturnsRecordContainerWithRecords()
    {
        $records = ['foo' => Doubles\MockedRecord::new()];

        $setup    = $this->builder($records);
        $expected = new RecordContainer($this->records($records));
        $this->assertEquals($expected, $setup->container());
    }

    public function testSetupWithSubContainers_container_ReturnsCompositeContainer()
    {
        $records    = ['foo' => Doubles\MockedRecord::new()];
        $containers = ['bar' => Doubles\FakeContainer::new()];

        $setup    = $this->builder($records, $containers);
        $expected = new CompositeContainer($this->records($records), $containers);
        $this->assertEquals($expected, $setup->container());
    }

    public function testBuild_addRecord_WillCreateContainerWithAddedRecords()
    {
        $added = Doubles\MockedRecord::new('added');

        $setup = $this->builder();
        $setup->addRecord('foo', $added);
        $expected = new RecordContainer($this->records(['foo' => $added]));
        $this->assertEquals($expected, $setup->container());
    }

    public function testBuild_replaceRecord_WillCreateContainerWithReplacedRecords()
    {
        $records  = ['foo' => Doubles\MockedRecord::new('original')];
        $replaced = Doubles\MockedRecord::new('replaced');

        $setup = $this->builder($records);
        $setup->replaceRecord('foo', $replaced);
        $expected = new RecordContainer($this->records(['foo' => $replaced]));
        $this->assertEquals($expected, $setup->container());
    }

    public function testBuild_addContainer_WillCreateContainerWithAddedContainers()
    {
        $container = Doubles\FakeContainer::new();

        $setup = $this->builder();
        $setup->addContainer('test', $container);
        $expected = new CompositeContainer($this->records([]), ['test' => $container]);
        $this->assertEquals($expected, $setup->container());
    }

    public function testBuild_replaceContainer_WillCreateContainerWithReplacedContainers()
    {
        $original = Doubles\FakeContainer::new(['A' => 'original']);
        $replaced = Doubles\FakeContainer::new(['A' => 'replaced']);

        $setup = $this->builder([], ['foo' => $original]);
        $setup->replaceContainer('foo', $replaced);
        $expected = new CompositeContainer($this->records(), ['foo' => $replaced]);
        $this->assertEquals($expected, $setup->container());
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

    abstract protected function builder(array $records = [], array $containers = []): Setup\Build;

    abstract protected function records(array $records = []): Records;
}
