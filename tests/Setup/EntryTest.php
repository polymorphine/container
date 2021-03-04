<?php declare(strict_types=1);

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
use Polymorphine\Container\Setup\Entry;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Setup\Exception;
use Polymorphine\Container\Tests\Fixtures;
use Polymorphine\Container\Tests\Doubles;


class EntryTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Entry::class, $this->entry('id'));
    }

    public function testEntry_record_ChangesSetupRecords()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);
        $this->assertSame([], $setup->setRecords);
        $entry->record($fooRecord = Doubles\MockedRecord::new());
        $this->assertSame([['foo', $fooRecord]], $setup->setRecords);

        $entry = $this->entry('bar', $setup);
        $entry->record($barRecord = Doubles\MockedRecord::new());
        $this->assertSame([['foo', $fooRecord], ['bar', $barRecord]], $setup->setRecords);
    }

    public function testEntry_value_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->value('bar');
        $record = new Record\ValueRecord('bar');
        $this->assertEquals([['foo', $record]], $setup->setRecords);
    }

    public function testEntry_callback_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $object   = Fixtures\ExampleImpl::new();
        $callback = function () use ($object) { return $object; };

        $entry->callback($callback);
        $record = new Record\CallbackRecord($callback);
        $this->assertEquals([['foo', $record]], $changes = $setup->setRecords);

        /** @var Record $record */
        $record = $changes[0][1];
        $this->assertSame($object, $record->value(Doubles\FakeContainer::new()));
    }

    public function testEntry_instance_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->instance(Fixtures\ExampleImpl::class, 'foo', 'bar');
        $record = new Record\InstanceRecord(Fixtures\ExampleImpl::class, 'foo', 'bar');
        $this->assertEquals([['foo', $record]], $setup->setRecords);
    }

    public function testEntry_product_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->product('factory.id', 'create', 'bar', 'baz');
        $record = new Record\ProductRecord('factory.id', 'create', 'bar', 'baz');
        $this->assertEquals([['foo', $record]], $setup->setRecords);
    }

    public function testEntry_wrappedInstance_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->wrappedInstance(Fixtures\ExampleImpl::class, 'bar')
              ->with(Fixtures\DecoratorExample::class, 'foo', 'baz')
              ->with(Fixtures\DecoratorExample::class, 'qux', 'foo')
              ->compose();
        $record = new Record\InstanceRecord(Fixtures\ExampleImpl::class, 'bar');
        $record = new Record\ComposedInstanceRecord(Fixtures\DecoratorExample::class, $record, null, 'baz');
        $record = new Record\ComposedInstanceRecord(Fixtures\DecoratorExample::class, $record, 'qux', null);
        $this->assertEquals([['foo', $record]], $setup->setRecords);
    }

    public function testEntry_wrappedInstanceWithNoReferenceToWrappedObject_ThrowsException()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $wrapper = $entry->wrappedInstance(Fixtures\ExampleImpl::class, 'bar');
        $this->expectException(Exception\IntegrityConstraintException::class);
        $wrapper->with(Fixtures\DecoratorExample::class, 'not-foo', 'baz');
    }

    public function testEntry_container_SetsSetupContainer()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $this->assertSame([], $setup->setContainers);

        $fooContainer = Doubles\FakeContainer::new();
        $entry->container($fooContainer);
        $this->assertSame([['foo', $fooContainer]], $setup->setContainers);

        $entry = $this->entry('bar', $setup);

        $barContainer = Doubles\FakeContainer::new();
        $entry->container($barContainer);
        $this->assertSame([['foo', $fooContainer], ['bar', $barContainer]], $setup->setContainers);
    }

    private function builder(): Doubles\MockedBuild
    {
        return new Doubles\MockedBuild();
    }

    private function entry(string $id, Doubles\MockedBuild $build = null): Entry
    {
        return new Entry($id, $build ?? $this->builder());
    }
}
