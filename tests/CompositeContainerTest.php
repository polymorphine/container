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
use Polymorphine\Container\CompositeContainer;
use Polymorphine\Container\Records;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;


class CompositeContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container());
    }

    public function testContainer_getRecordId_ReturnsRecordValue()
    {
        $container = $this->container(['foo' => Doubles\MockedRecord::new('fooValue')]);
        $this->assertSame('fooValue', $container->get('foo'));
    }

    public function testContainer_getContainerId_ReturnsSubContainer()
    {
        $subContainer = Doubles\FakeContainer::new();
        $container    = $this->container(['foo' => Doubles\MockedRecord::new('fooValue')], ['sub' => $subContainer]);
        $this->assertSame($subContainer, $container->get('sub'));
    }

    public function testContainer_getWithContainerPrefixedId_ReturnsSubContainerValue()
    {
        $subContainer = Doubles\FakeContainer::new(['foo' => 'subFooValue']);
        $container    = $this->container(['foo' => Doubles\MockedRecord::new('fooValue')], ['sub' => $subContainer]);
        $this->assertSame('subFooValue', $container->get('sub.foo'));
    }

    public function testContainerWithOverlappingEntries_getWithConflictedId_ReturnsValueFromContainer()
    {
        $subContainer = Doubles\FakeContainer::new(['bar' => 'subFooValue']);
        $container    = $this->container(['foo' => Doubles\MockedRecord::new('inaccessible')], ['foo' => $subContainer]);
        $this->assertSame($subContainer, $container->get('foo'));

        $container = $this->container(['foo.bar' => Doubles\MockedRecord::new('inaccessible')], ['foo' => $subContainer]);
        $this->assertSame('subFooValue', $container->get('foo.bar'));
    }

    public function testContainer_has_ReturnsWhetherEntryIsDefinedAndAccessible()
    {
        $container = $this->container([
            'foo.something' => Doubles\MockedRecord::new('inaccessible'),
            'bar.something' => Doubles\MockedRecord::new(null)
        ], [
            'foo' => Doubles\FakeContainer::new(['bar' => null, 'baz' => 0])
        ]);

        $this->assertFalse($container->has('foo.something'));
        $this->assertTrue($container->has('bar.something'));
        $this->assertTrue($container->has('foo.bar'));
        $this->assertTrue($container->has('foo.baz'));
        $this->assertTrue($container->has('foo'));
        $this->assertFalse($container->has('bar'));
    }

    /**
     * @dataProvider undefinedEntries
     *
     * @param string $id
     */
    public function testContainer_getForUndefinedEntry_ThrowsException(string $id)
    {
        $container = $this->container([
            'foo.something' => Doubles\MockedRecord::new('inaccessible'),
            'foo.another'   => Doubles\MockedRecord::new('inaccessible'),
            'bar.something' => Doubles\MockedRecord::new(null)
        ], [
            'foo' => Doubles\FakeContainer::new(['bar' => null, 'baz' => 0])
        ]);

        $this->expectException(NotFoundExceptionInterface::class);
        $container->get($id);
    }

    public function undefinedEntries()
    {
        return [['foo.something'], ['bar'], ['foo.another'], ['bar.something.else']];
    }

    public function testContainer_getEntryWithReferencesToContainer_ReturnsResolvedValue()
    {
        $container = $this->container([
            'foo' => Doubles\MockedRecord::new(function (ContainerInterface $c) {
                $function = $c->get('bar');
                return $function($c->get('sub.foo'));
            }),
            'bar' => Doubles\MockedRecord::new(function (ContainerInterface $c) {
                return function (string $foo) use ($c) { return $foo . ' + ' . $c->get('sub.bar'); };
            })
        ], [
            'sub' => Doubles\FakeContainer::new(['foo' => 'subFoo', 'bar' => 'subBar'])
        ]);

        $this->assertSame('subFoo + subBar', $container->get('foo'));
    }

    public function testContainerWithTrackedRecords_getEntryWithUndefinedContainerReference_ThrowsExceptionWithFullCallStack()
    {
        $records = [
            'foo' => Doubles\MockedRecord::new(function (ContainerInterface $c) { return $c->get('sub.foo') . $c->get('bar'); }),
            'bar' => Doubles\MockedRecord::new(function (ContainerInterface $c) { return $c->get('sub.undefined'); })
        ];

        $containers = [
            'sub' => Doubles\FakeContainer::new(['foo' => 'subFoo'])
        ];

        $container = new CompositeContainer(new Records\TrackedRecords($records), $containers);

        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Sub-container `sub.undefined` entry not found [call stack: foo->bar->sub.undefined]');
        $container->get('foo');
    }

    private function container(array $records = [], array $containers = [])
    {
        return new CompositeContainer(new Records($records), $containers);
    }
}
