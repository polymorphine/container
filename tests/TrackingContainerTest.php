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
use Polymorphine\Container\TrackingContainer;
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class TrackingContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(TrackingContainer::class, $this->builder()->container());
    }

    public function testDirectCircularCall_ThrowsException()
    {
        $setup = $this->builder();
        $setup->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $container->get('ref.self');
    }

    public function testIndirectCircularCall_ThrowsException()
    {
        $setup = $this->builder();
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $setup->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->has('ref.dependency') ? $c->get('ref.dependency') : null;
        });
        $setup->entry('ref.dependency')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $container->get('ref');
    }

    public function testMultipleCallsAreNotCircular()
    {
        $setup = $this->builder();
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.multiple') . ':' . $c->get('ref.multiple');
        });
        $setup->entry('ref.multiple')->set('Test');
        $this->assertSame('Test:Test', $setup->container()->get('ref'));
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
        $this->assertSame('Test:Test', $setup->container()->get('ref'));
    }

    public function testIndirectMultipleCallToPassedContainer_ThrowsException()
    {
        $setup = $this->builder();
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c;
        });
        $container = $setup->container();

        $trackedContainer = $container->get('ref');
        $this->expectException(Exception\CircularReferenceException::class);
        $trackedContainer->get('ref');
    }

    private function builder(array $data = [])
    {
        return new ContainerSetup($data, true);
    }
}