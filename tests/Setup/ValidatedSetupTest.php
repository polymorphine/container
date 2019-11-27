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

use Polymorphine\Container\Tests\Fixtures\ExampleImpl;
use Polymorphine\Container\Tests\SetupTest;
use Polymorphine\Container\Setup;
use Polymorphine\Container\Records;
use Polymorphine\Container\Tests\Doubles;


class ValidatedSetupTest extends SetupTest
{
    public function testValidatedSetup_InstantiationWithInvalidRecordType_ThrowsException()
    {
        $this->expectException(Setup\Exception\InvalidTypeException::class);
        $this->builder(['foo' => ExampleImpl::new()]);
    }

    public function testValidatedSetup_InstantiationWithInvalidContainerType_ThrowsException()
    {
        $this->expectException(Setup\Exception\InvalidTypeException::class);
        $this->builder([], ['foo' => ExampleImpl::new()]);
    }

    public function testValidatedSetup_InvalidContainerId_ThrowsException()
    {
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $this->builder([], ['foo.bar' => Doubles\FakeContainer::new()]);
    }

    public function testValidatedSetup_ContainerIdTakenByRecord_ThrowsException()
    {
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $this->builder(['foo' => Doubles\MockedRecord::new()], ['foo' => Doubles\FakeContainer::new()]);
    }

    public function testValidatedSetup_addRecordMethodForDefinedRecord_ThrowsException()
    {
        $setup = $this->builder(['defined' => Doubles\MockedRecord::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('defined', Doubles\MockedRecord::new());
    }

    public function testValidatedSetup_addContainerMethodForDefinedContainer_ThrowsException()
    {
        $setup = $this->builder([], ['defined' => Doubles\FakeContainer::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addContainer('defined', Doubles\FakeContainer::new());
    }

    public function testValidatedSetup_addRecordMethodWithDefinedContainerId_ThrowsException()
    {
        $setup = $this->builder([], ['defined' => Doubles\FakeContainer::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('defined', Doubles\MockedRecord::new());
    }

    public function testValidatedSetup_addContainerWithIdTakenByRecordsPrefix_ThrowsException()
    {
        $setup = $this->builder(['foo.bar' => Doubles\MockedRecord::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addContainer('foo', Doubles\FakeContainer::new());
    }

    public function testValidatedSetup_addRecordMethodWithDefinedContainerPrefix_ThrowsException()
    {
        $setup = $this->builder([], ['defined' => Doubles\FakeContainer::new()]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('defined.record', Doubles\MockedRecord::new());
    }

    public function testValidatedSetup_replaceRecordMethodForUndefinedRecord_ThrowsException()
    {
        $setup = $this->builder();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->replaceRecord('undefined', Doubles\MockedRecord::new());
    }

    public function testValidatedSetup_replaceContainerMethodForUndefinedContainer_ThrowsException()
    {
        $setup = $this->builder();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->replaceContainer('undefined', Doubles\FakeContainer::new());
    }

    protected function builder(array $records = [], array $containers = []): Setup
    {
        return Setup::validated($records, $containers);
    }

    protected function records(array $records = []): Records
    {
        return new Records\TrackedRecords($records);
    }
}
