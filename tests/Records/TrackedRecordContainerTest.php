<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Records;

use Polymorphine\Container\Tests\RecordContainerTest;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\Records;
use Polymorphine\Container\Tests\Doubles\MockedRecord;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class TrackedRecordContainerTest extends RecordContainerTest
{
    public function testTrackedContainer_getUndefinedRecordValue_ThrowsExceptionWithCallStack()
    {
        $container = $this->container([
            'foo' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('bar'); }),
            'bar' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('baz'); }),
            'baz' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('undefined'); })
        ]);

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('foo->bar->baz->undefined');
        $container->get('foo');
    }

    public function testTrackedContainer_getSelfReferencedRecord_ThrowsExceptionWithCallStack()
    {
        $container = $this->container([
            'foo' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('bar'); }),
            'bar' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('baz'); }),
            'baz' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('foo'); })
        ]);

        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('foo->bar->baz->foo');
        $container->get('foo');
    }

    public function testTrackedContainer_getRecordValueWithEncapsulatedSelfReference_ReturnsRecordValue()
    {
        $container = $this->container([
            'foo' => MockedRecord::new(function (ContainerInterface $c) { return $c->get('bar'); }),
            'bar' => MockedRecord::new(function (ContainerInterface $c) {
                return function ($string = null) use ($c) { return $string ?: $c->get('foo'); };
            })
        ]);

        $value   = $container->get('foo');
        $derived = $value();
        $this->assertSame('text', $value('text'));
        $this->assertSame($value('text'), $derived('text'));
    }

    protected function container(array $records = []): RecordContainer
    {
        return new RecordContainer(new Records\TrackedRecords($records));
    }
}
