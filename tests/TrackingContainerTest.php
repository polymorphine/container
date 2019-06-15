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
use Polymorphine\Container\ConfigContainer;
use Polymorphine\Container\Container;
use Polymorphine\Container\CompositeRecordCollection;
use Polymorphine\Container\Tests\Fixtures\Example\DecoratingExampleClass;
use Polymorphine\Container\Tests\Fixtures\Example\ExampleClass;
use Polymorphine\Container\TrackingContainer;
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;


class TrackingContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(TrackingContainer::class, $this->builder()->container(true));
        $this->assertInstanceOf(ContainerExceptionInterface::class, new Exception\CircularReferenceException());
    }

    public function testDirectCircularCall_ThrowsException()
    {
        $setup = $this->builder();
        $setup->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container(true);
        $this->expectException(Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('ref.self->ref.self');
        $container->get('ref.self');
    }

    public function testIndirectCircularCall_ThrowsException()
    {
        $setup = $this->builder();
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $setup->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.dependency');
        });
        $setup->entry('ref.dependency')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container(true);
        $this->expectException(Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('ref->ref.self->ref.dependency->ref.self');
        $container->get('ref');
    }

    public function testMultipleCallsAreNotCircular()
    {
        $setup = $this->builder(['config' => 'value']);
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.multiple') . ':' . $c->get('ref.multiple') . ':' . $c->get('.config');
        });
        $setup->entry('ref.multiple')->set('Test');
        $this->assertSame('Test:Test:value', $setup->container(true)->get('ref'));
    }

    public function testMultipleIndirectCallsAreNotCircular()
    {
        $setup = $this->builder();
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('function')($c);
        });
        $setup->entry('function')->invoke(function (ContainerInterface $c) {
            return function (ContainerInterface $test) use ($c) {
                return $c->get('ref.multiple') . ':' . $test->get('ref.multiple');
            };
        });
        $setup->entry('ref.multiple')->set('Test');
        $this->assertSame('Test:Test', $setup->container(true)->get('ref'));
    }

    public function testIndirectMultipleCallToPassedContainer_ThrowsException()
    {
        $setup = $this->builder();
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c;
        });
        $container = $setup->container(true);

        $trackedContainer = $container->get('ref');
        $this->expectException(Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('ref->ref');
        $trackedContainer->get('ref');
    }

    public function testCallStackIsAddedToContainerExceptionMessage()
    {
        $setup = $this->builder(['config' => 'value']);
        $setup->entry('A')->set(function () {});
        $setup->entry('B')->invoke(function (Container $c) {
            return new ExampleClass($c->get('A'), $c->get('undefined'));
        });
        $setup->entry('C')->compose(DecoratingExampleClass::class, 'B', '.config');

        $container = $setup->container(true);
        $this->expectExceptionMessage('C->B->undefined');
        $container->get('C');
    }

    private function builder(array $config = [], array $records = [])
    {
        return new ContainerSetup(new CompositeRecordCollection(new ConfigContainer($config), $records));
    }
}
