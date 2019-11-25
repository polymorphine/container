<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Records;

use PHPUnit\Framework\TestCase;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Tests\Doubles;
use Polymorphine\Container\Tests\Fixtures;
use Psr\Container\ContainerInterface;


class RecordTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(Record::class, new Record\ValueRecord('foo'));
        $this->assertInstanceOf(Record::class, new Record\CallbackRecord(function () {}));
        $this->assertInstanceOf(Record::class, new Record\InstanceRecord(Fixtures\ExampleImpl::class, 'foo.id'));
        $this->assertInstanceOf(Record::class, new Record\ProductRecord('factory.id', 'create', 'foo.id'));
        $this->assertInstanceOf(Record::class, new Record\ComposedInstanceRecord('foo.id', Doubles\MockedRecord::new(), []));
    }

    public function testValueRecord_value_ReturnsStoredValue()
    {
        $expected = 'test string';
        $record   = new Record\ValueRecord($expected);
        $this->assertSame($expected, $record->value(Doubles\FakeContainer::new()));
    }

    public function testCallbackRecord_value_ReturnsInvokedCallbackResult()
    {
        $expected = 'test string';
        $record   = new Record\CallbackRecord(function () use ($expected) { return $expected; });
        $this->assertSame($expected, $record->value(Doubles\FakeContainer::new()));
    }

    public function testCallbackRecord_value_CanGetValueFromContainer()
    {
        $expected = 'test string';
        $record   = new Record\CallbackRecord(function (ContainerInterface $c) { return $c->get('expected'); });
        $this->assertSame($expected, $record->value(Doubles\FakeContainer::new(['expected' => $expected])));
    }

    public function testInstanceRecord_value_ReturnsInstantiatedObject()
    {
        $container = Doubles\FakeContainer::new([
            'callback' => function (string $string) { return 'Test ' . $string; },
            'string'   => 'string'
        ]);
        $expected = Fixtures\ExampleImpl::new('Test string');
        $record   = new Record\InstanceRecord(Fixtures\ExampleImpl::class, 'callback', 'string');
        $this->assertEquals($expected, $record->value($container));
    }

    public function testProductRecord_value_ReturnsFactoryCreatedObject()
    {
        $container = Doubles\FakeContainer::new([
            'stringA' => 'Test',
            'stringB' => 'string',
            'factory' => new Fixtures\FactoryExample()
        ]);
        $expected = Fixtures\ExampleImpl::new('Test string');
        $record   = new Record\ProductRecord('factory', 'create', 'stringA', 'stringB');
        $this->assertEquals($expected, $record->value($container));
    }

    public function testComposedInstanceRecord_value_ReturnsComposedObject()
    {
        $container = Doubles\FakeContainer::new([
            'callback' => function (string $string) { return 'Test ' . $string; },
            'textA'    => 'wrapped',
            'textB'    => 'twice'
        ]);
        $expected = new Fixtures\DecoratorExample(Fixtures\ExampleImpl::new('Test string'), 'wrapped twice');
        $record = new Record\ComposedInstanceRecord('wrapped', new Record\ValueRecord('string'), [
            [Fixtures\ExampleImpl::class, ['callback', 'wrapped']],
            [Fixtures\DecoratorExample::class, ['wrapped', 'textA']],
            [Fixtures\DecoratorExample::class, ['wrapped', 'textB']]
        ]);
        $this->assertEquals($expected, $record->value($container));
    }

    /**
     * @dataProvider lazyRecords
     *
     * @param Record $record
     */
    public function testLazyRecordValuesAreCached(Record $record)
    {
        $container = Doubles\FakeContainer::new([
            'stringA'  => 'foo',
            'stringB'  => 'bar',
            'callback' => function (string $string) { return $string; },
            'factory'  => new Fixtures\FactoryExample()
        ]);
        $this->assertSame($record->value($container), $record->value($container));
    }

    public function lazyRecords()
    {
        return [
            [new Record\CallbackRecord(function () { return Fixtures\ExampleImpl::new(); })],
            [new Record\InstanceRecord(Fixtures\ExampleImpl::class, 'callback', 'stringA')],
            [new Record\ProductRecord('factory', 'create', 'stringA', 'stringB')],
            [new Record\ComposedInstanceRecord('wrap', new Record\ValueRecord('foo'), [
                [Fixtures\ExampleImpl::class, ['callback', 'wrap']],
                [Fixtures\DecoratorExample::class, ['wrap', 'stringA']]
            ])]
        ];
    }
}
