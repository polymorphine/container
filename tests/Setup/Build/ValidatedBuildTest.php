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
use Polymorphine\Container\Tests\Fixtures\ExampleImpl;
use Polymorphine\Container\Tests\Doubles;
use Polymorphine\Container\Records;
use Polymorphine\Container\Setup;


class ValidatedBuildTest extends BuildTest
{
    public function testValidatedBuild_InstantiationWithInvalidRecordType_ThrowsException()
    {
        $this->expectException(Setup\Exception\InvalidTypeException::class);
        $this->builder(['foo' => ExampleImpl::new()]);
    }

    public function testValidatedBuild_InstantiationWithInvalidContainerType_ThrowsException()
    {
        $this->expectException(Setup\Exception\InvalidTypeException::class);
        $this->builder([], ['foo' => ExampleImpl::new()]);
    }

    public function testValidatedBuild_addRecordWithAlreadyDefinedId_ThrowsException()
    {
        $setup = $this->builder(['defined' => Doubles\MockedRecord::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('defined', Doubles\MockedRecord::new());
    }

    public function testValidatedBuild_addContainerWithAlreadyDefinedId_ThrowsException()
    {
        $setup = $this->builder([], ['defined' => Doubles\FakeContainer::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addContainer('defined', Doubles\FakeContainer::new());
    }

    public function testValidatedBuild_replaceRecordWithUndefinedId_ThrowsException()
    {
        $setup = $this->builder();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->replaceRecord('undefined', Doubles\MockedRecord::new());
    }

    public function testValidatedBuild_replaceContainerWithUndefinedId_ThrowsException()
    {
        $setup = $this->builder();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->replaceContainer('undefined', Doubles\FakeContainer::new());
    }

    public function testValidatedBuild_addRecordWithDefinedContainerId_ThrowsException()
    {
        $setup = $this->builder([], ['defined' => Doubles\FakeContainer::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('defined', Doubles\MockedRecord::new());
    }

    public function testValidatedBuild_InvalidContainerId_ThrowsException()
    {
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $this->builder([], ['foo.bar' => Doubles\FakeContainer::new()]);
    }

    public function testValidatedBuild_ContainerIdTakenByRecord_ThrowsException()
    {
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $this->builder(['foo' => Doubles\MockedRecord::new()], ['foo' => Doubles\FakeContainer::new()]);
    }

    public function testValidatedBuild_addContainerWithIdTakenByRecordsPrefix_ThrowsException()
    {
        $setup = $this->builder(['foo.bar' => Doubles\MockedRecord::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addContainer('foo', Doubles\FakeContainer::new());
    }

    public function testValidatedBuild_addRecordMethodWithDefinedContainerPrefix_ThrowsException()
    {
        $setup = $this->builder([], ['defined' => Doubles\FakeContainer::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('defined.record', Doubles\MockedRecord::new());
    }

    protected function builder(array $records = [], array $containers = []): Setup\Build
    {
        return new Setup\Build\ValidatedBuild($records, $containers);
    }

    protected function records(array $records = []): Records
    {
        return new Records\TrackedRecords($records);
    }
}
