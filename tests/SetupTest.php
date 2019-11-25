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
use Polymorphine\Container\Records;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;


abstract class SetupTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Setup::class, $this->builder());
    }

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

    public function testSetup_addRecords_WillCreateContainerWithAddedRecords()
    {
        $records = ['foo' => Doubles\MockedRecord::new(), 'bar' => Doubles\MockedRecord::new()];

        $setup = $this->builder();
        $setup->addRecords($records);
        $expected = new RecordContainer($this->records($records));
        $this->assertEquals($expected, $setup->container());
    }

    public function testSetup_replaceRecord_WillCreateContainerWithReplacedRecords()
    {
        $records  = ['foo' => Doubles\MockedRecord::new('original')];
        $replaced = Doubles\MockedRecord::new('replaced');

        $setup = $this->builder($records);
        $setup->replaceRecord('foo', $replaced);
        $expected = new RecordContainer($this->records(['foo' => $replaced]));
        $this->assertEquals($expected, $setup->container());
    }

    public function testSetup_addContainer_WillCreateContainerWithAddedContainers()
    {
        $container = Doubles\FakeContainer::new();

        $setup = $this->builder();
        $setup->addContainer('test', $container);
        $expected = new CompositeContainer($this->records([]), ['test' => $container]);
        $this->assertEquals($expected, $setup->container());
    }

    public function testSetup_replaceContainer_WillCreateContainerWithReplacedContainers()
    {
        $original = Doubles\FakeContainer::new(['A' => 'original']);
        $replaced = Doubles\FakeContainer::new(['A' => 'replaced']);

        $setup = $this->builder([], ['foo' => $original]);
        $setup->replaceContainer('foo', $replaced);
        $expected = new CompositeContainer($this->records(), ['foo' => $replaced]);
        $this->assertEquals($expected, $setup->container());
    }

    public function testSetup_add_ReturnsAddEntryObject()
    {
        $setup    = $this->builder();
        $expected = new Setup\Entry\AddEntry('foo', $setup);
        $this->assertEquals($expected, $setup->add('foo'));
    }

    public function testSetup_replace_ReturnsReplaceEntryObject()
    {
        $setup    = $this->builder();
        $expected = new Setup\Entry\ReplaceEntry('foo', $setup);
        $this->assertEquals($expected, $setup->replace('foo'));
    }

    public function testSetup_decorate_ReturnsReplacingWrapper()
    {
        $decorated = Doubles\MockedRecord::new('decorated');

        $setup    = $this->builder(['foo' => $decorated]);
        $expected = new Setup\Entry\Wrapper('foo', $decorated, new Setup\Entry\ReplaceEntry('foo', $setup));
        $this->assertEquals($expected, $setup->decorate('foo'));
    }

    public function testSetup_decorateUndefinedRecord_ThrowsException()
    {
        $setup = $this->builder(['foo' => Doubles\MockedRecord::new('not decorated')]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->decorate('bar');
    }

    abstract protected function builder(array $records = [], array $containers = []): Setup;

    abstract protected function records(array $records = []): Records;
}
