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
use Polymorphine\Container\Setup\Entry;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Setup\Exception\IntegrityConstraintException;
use Polymorphine\Container\Tests\Doubles;
use Polymorphine\Container\Tests\Fixtures\DecoratorExample;
use Polymorphine\Container\Tests\Fixtures\ExampleImpl;


abstract class EntryTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Entry::class, $this->entry('id'));
    }

    public function testEntry_record_ChangesSetupRecords()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);
        $this->assertSame([], $setup->recordChanges());
        $entry->record($fooRecord = Doubles\MockedRecord::new());
        $this->assertSame([['foo', $fooRecord]], $setup->recordChanges());

        $entry = $this->entry('bar', $setup);
        $entry->record($barRecord = Doubles\MockedRecord::new());
        $this->assertSame([['foo', $fooRecord], ['bar', $barRecord]], $setup->recordChanges());
    }

    public function testEntry_value_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->value('bar');
        $record = new Record\ValueRecord('bar');
        $this->assertEquals([['foo', $record]], $setup->recordChanges());
    }

    public function testEntry_callback_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $object   = ExampleImpl::new();
        $callback = function () use ($object) { return $object; };

        $entry->callback($callback);
        $record = new Record\CallbackRecord($callback);
        $this->assertEquals([['foo', $record]], $changes = $setup->recordChanges());

        /** @var Record $record */
        $record = $changes[0][1];
        $this->assertSame($object, $record->value(Doubles\FakeContainer::new()));
    }

    public function testEntry_instance_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->instance(ExampleImpl::class, 'foo', 'bar');
        $record = new Record\InstanceRecord(ExampleImpl::class, 'foo', 'bar');
        $this->assertEquals([['foo', $record]], $setup->recordChanges());
    }

    public function testEntry_product_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->product('factory.id', 'create', 'bar', 'baz');
        $record = new Record\ProductRecord('factory.id', 'create', 'bar', 'baz');
        $this->assertEquals([['foo', $record]], $setup->recordChanges());
    }

    public function testEntry_wrappedInstance_SetsSetupValueRecord()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $entry->wrappedInstance(ExampleImpl::class, 'bar')
              ->with(DecoratorExample::class, 'foo', 'baz')
              ->with(DecoratorExample::class, 'qux', 'foo')
              ->compose();
        $record = new Record\ComposedInstanceRecord('foo', new Record\InstanceRecord(ExampleImpl::class, 'bar'), [
            [DecoratorExample::class, ['foo', 'baz']],
            [DecoratorExample::class, ['qux', 'foo']]
        ]);
        $this->assertEquals([['foo', $record]], $setup->recordChanges());
    }

    public function testEntry_wrappedInstanceWithNoReferenceToWrappedObject_ThrowsException()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $wrapper = $entry->wrappedInstance(ExampleImpl::class, 'bar');
        $this->expectException(IntegrityConstraintException::class);
        $wrapper->with(DecoratorExample::class, 'not-foo', 'baz');
    }

    public function testEntry_container_SetsSetupContainer()
    {
        $setup = $this->builder();
        $entry = $this->entry('foo', $setup);

        $this->assertSame([], $setup->containerChanges());

        $fooContainer = Doubles\FakeContainer::new();
        $entry->container($fooContainer);
        $this->assertSame([['foo', $fooContainer]], $setup->containerChanges());

        $entry = $this->entry('bar', $setup);

        $barContainer = Doubles\FakeContainer::new();
        $entry->container($barContainer);
        $this->assertSame([['foo', $fooContainer], ['bar', $barContainer]], $setup->containerChanges());
    }

    abstract protected function builder(): Doubles\MockedBuild;

    abstract protected function entry(string $name, Doubles\MockedBuild $build = null): Entry;
}
