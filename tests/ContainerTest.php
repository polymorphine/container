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
use Polymorphine\Container\Container;
use Polymorphine\Container\Factory;
use Polymorphine\Container\Record;
use Polymorphine\Container\RecordEntry;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class ContainerTest extends TestCase
{
    protected function factory(array $data = []) {
        return new Factory($data);
    }

    protected function withBasicSettings() {
        $factory = $this->factory();
        $factory->record('test', new Record\DirectRecord('Hello World!'));
        $factory->record('lazy', new Record\LazyRecord(function () { return 'Lazy Foo'; }));

        return $factory;
    }

    public function testInstantiation() {
        $this->assertInstanceOf(ContainerInterface::class, $this->factory()->container());
    }

    public function testConfiguredRecordsAreAvailableFromContainer() {
        $container = $this->withBasicSettings()->container();

        $this->assertTrue($container->has('test') && $container->has('lazy'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
    }

    public function testClosuresForLazyLoadedValuesCanAccessContainer() {
        $factory = $this->withBasicSettings();
        $factory->record('bar', new Record\LazyRecord(function (ContainerInterface $c) {
            return substr($c->get('test'), 0, 6) . $c->get('lazy') . '!';
        }));
        $container = $factory->container();

        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testInvalidContainerIdType_ThrowsException() {
        $container = $this->factory()->container();
        $this->expectException(ContainerExceptionInterface::class);
        $container->has(23);
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException() {
        $container = $this->factory()->container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('not.set');
    }

    public function testRegistryConstructorRecordsAreAvailableFromContainer() {
        $expected = [
            'test' => 'Hello World!',
            'category.first' => 'one',
            'category.second' => 'two',
            'array' => [1,2,3],
            'assoc' => ['first' => 1, 'second' => 2],
            'callback' => function () { return 'first'; },
            'lazy.hello' => 'Hello World!',
            'lazy.goodbye' => 'see ya!'
        ];

        $container = $this->factory([
            'test' => new Record\DirectRecord('Hello World!'),
            'category.first' => new Record\DirectRecord('one'),
            'category.second' => new Record\DirectRecord('two'),
            'array' => new Record\DirectRecord([1,2,3]),
            'assoc' => new Record\DirectRecord(['first' => 1, 'second' => 2]),
            'callback' => new Record\DirectRecord($expected['callback']),
            'lazy.hello' => new Record\LazyRecord(function (ContainerInterface $c) { return $c->get('test'); }),
            'lazy.goodbye' => new Record\LazyRecord(function () { return 'see ya!'; })
        ])->container();

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    public function testConstructWithNotAssociativeArray_ThrowsException() {
        $this->expectException(ContainerExceptionInterface::class);
        $this->factory(['first' => 'ok', 2 => 'not ok'])->container();
    }

    public function testCallbacksCannotModifyRegistry() {
        $factory = $this->factory();
        $factory->record('lazyModifier', new Record\LazyRecord(function ($c) {
            $vars = get_object_vars($c);
            return isset($vars['records']);
        }));
        $this->assertFalse($factory->container()->get('lazyModifier'));
    }

    public function testOverwritingExistingKey_ThrowsException() {
        $factory = $this->factory(['test' => new Record\DirectRecord('foo')]);
        $this->expectException(Exception\InvalidIdException::class);
        $factory->record('test', new Record\DirectRecord('bar'));
    }

    public function testNumericId_ThrowsException() {
        $factory = $this->factory();
        $this->expectException(Exception\InvalidIdException::class);
        $factory->record('74', new Record\LazyRecord(function () { return 'foo'; }));
    }

    public function testEmptyFactoryId_ThrowsException() {
        $factory = $this->factory();
        $this->expectException(Exception\InvalidIdException::class);
        $factory->record('', new Record\DirectRecord(function () { return 'foo'; }));
    }

    public function testEmptyIdContainerCall_ThrowsException() {
        $container = $this->withBasicSettings()->container();
        $this->expectException(Exception\InvalidIdException::class);
        $container->has('');
    }

    public function testInvalidConstructorType_ThrowsException() {
        $this->expectException(Exception\InvalidArgumentException::class);
        new Container(['test' => 'value']);
    }

    /**
     * @dataProvider inputScenarios
     * @param $method
     * @param $id
     * @param $value
     * @param $result
     */
    public function testInputProxyMethods($method, $id, $value, $result) {
        $factory = $this->factory();
        $proxy   = new RecordEntry($id, $factory);
        $proxy->{$method}($value);
        $container = $factory->container();
        $this->assertTrue($container->has($id));
        $this->assertSame($result ?: $value, $container->get($id));
    }

    public function inputScenarios() {
        return [
            ['value', 'direct', 'direct value', 'direct value'],
            ['lazy', 'lazy', function () { return 'lazy value'; }, 'lazy value'],
            ['record', 'directRecord', new Record\DirectRecord('direct value'), 'direct value'],
            ['record', 'lazyRecord', new Record\LazyRecord(function () { return 'lazy value'; }), 'lazy value']
        ];
    }
}
