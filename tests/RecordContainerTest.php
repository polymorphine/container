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
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\Records;
use Polymorphine\Container\Tests\Doubles\MockedRecord;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;


class RecordContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container());
    }

    public function testContainer_hasForDefinedRecords_ReturnsTrue()
    {
        $container = $this->container($records = [
            'foo' => MockedRecord::new('foo'),
            'bar' => MockedRecord::new(''),
            'baz' => MockedRecord::new(false),
            'qux' => MockedRecord::new(null)
        ]);

        foreach ($records as $id => $record) {
            $this->assertTrue($container->has($id));
        }
    }

    public function testContainer_hasForUndefinedRecords_ReturnsFalse()
    {
        $container = $this->container($records = [
            'foo' => MockedRecord::new('foo'),
            'bar' => MockedRecord::new('')
        ]);

        foreach (['baz', 'qux'] as $id) {
            $this->assertFalse($container->has($id));
        }
    }

    public function testContainer_get_ReturnsValueFromRecord()
    {
        $container = $this->container($records = [
            'foo' => MockedRecord::new('foo'),
            'bar' => MockedRecord::new(''),
            'baz' => MockedRecord::new(false),
            'qux' => MockedRecord::new(null)
        ]);

        foreach ($records as $id => $record) {
            $this->assertSame($records[$id]->value, $container->get($id));
        }
    }

    public function testContainer_getForUndefinedRecord_ThrowsException()
    {
        $container = $this->container(['foo' => MockedRecord::new('example')]);
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('undefined');
    }

    public function testContainer_get_PassesItselfToBuiltRecord()
    {
        $container = $this->container(['foo' => $record = MockedRecord::new('example')]);
        $container->get('foo');
        $this->assertSame($record->passedContainer, $container);
    }

    protected function container(array $records = []): RecordContainer
    {
        return new RecordContainer(new Records($records));
    }
}
