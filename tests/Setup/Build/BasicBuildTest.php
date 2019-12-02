<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Setup\Build;

use Polymorphine\Container\Tests\Setup\BuildTest;
use Polymorphine\Container\Records;
use Polymorphine\Container\Setup;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;
use Polymorphine\Container\Tests\Doubles;


class BasicBuildTest extends BuildTest
{
    public function testBasicBuild_addRecordWithAlreadyDefinedId_ReplacesRecord()
    {
        $setup = $this->builder(['foo' => Doubles\MockedRecord::new('original')]);
        $setup->addRecord('foo', $added = Doubles\MockedRecord::new('added'));
        $expected = new RecordContainer($this->records(['foo' => $added]));
        $this->assertEquals($expected, $setup->container());
    }

    public function testBasicBuild_addContainerWithAlreadyDefinedId_ReplacesContainer()
    {
        $setup = $this->builder([], ['foo' => Doubles\FakeContainer::new(['defined'])]);
        $setup->addContainer('foo', $added = Doubles\FakeContainer::new());
        $expected = new CompositeContainer($this->records([]), ['foo' => $added]);
        $this->assertEquals($expected, $setup->container());
    }

    public function testBasicBuild_replaceRecordWithUndefinedId_AddsRecord()
    {
        $setup = $this->builder();
        $setup->replaceRecord('undefined', $replaced = Doubles\MockedRecord::new());
        $expected = new RecordContainer($this->records(['undefined' => $replaced]));
        $this->assertEquals($expected, $setup->container());
    }

    public function testBasicBuild_replaceContainerWithUndefinedId_AddsContainer()
    {
        $setup = $this->builder();
        $setup->replaceContainer('foo', $replaced = Doubles\FakeContainer::new(['A' => 'replaced']));
        $expected = new CompositeContainer($this->records(), ['foo' => $replaced]);
        $this->assertEquals($expected, $setup->container());
    }

    protected function builder(array $records = [], array $containers = []): Setup\Build
    {
        return new Setup\Build\BasicBuild($records, $containers);
    }

    protected function records(array $records = []): Records
    {
        return new Records($records);
    }
}
