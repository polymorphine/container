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
use Polymorphine\Container\ContainerSetup;
use Polymorphine\Container\Record;
use Polymorphine\Container\Exception;
use Polymorphine\Container\RecordEntry;
use Polymorphine\Container\Tests\Doubles\DecoratingExampleClass;
use Polymorphine\Container\Tests\Doubles\ExampleClass;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class ContainerTest extends TestCase
{
    protected function setup(array $data = [])
    {
        return new ContainerSetup($data);
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->setup()->container());
    }

    public function testConfiguredRecordsAreAvailableFromContainer()
    {
        $container = $this->withBasicSettings()->container();

        $this->assertTrue($container->has('test') && $container->has('lazy'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
    }

    public function testClosuresForLazyLoadedValuesCanAccessContainer()
    {
        $setup = $this->withBasicSettings();
        $setup->push('bar', new Record\LazyRecord(function (ContainerInterface $c) {
            return substr($c->get('test'), 0, 6) . $c->get('lazy') . '!';
        }));
        $container = $setup->container();

        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testGivenContainerWithFalsyValues_HasMethodReturnsTrue()
    {
        $container = new Container([
            'null' => new Record\DirectRecord(null),
            'false' => new Record\DirectRecord(false)
        ]);

        $this->assertTrue($container->has('null'));
        $this->assertTrue($container->has('false'));
    }

    public function testInvalidContainerIdType_ThrowsException()
    {
        $container = $this->setup()->container();
        $this->expectException(ContainerExceptionInterface::class);
        $container->has(23);
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException()
    {
        $container = $this->setup()->container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('not.set');
    }

    public function testRegistryConstructorRecordsAreAvailableFromContainer()
    {
        $expected = [
            'test' => 'Hello World!',
            'category.first' => 'one',
            'category.second' => 'two',
            'array' => [1, 2, 3],
            'assoc' => ['first' => 1, 'second' => 2],
            'callback' => function () { return 'first'; },
            'lazy.hello' => 'Hello World!',
            'lazy.goodbye' => 'see ya!'
        ];

        $container = new Container([
            'test' => new Record\DirectRecord('Hello World!'),
            'category.first' => new Record\DirectRecord('one'),
            'category.second' => new Record\DirectRecord('two'),
            'array' => new Record\DirectRecord([1, 2, 3]),
            'assoc' => new Record\DirectRecord(['first' => 1, 'second' => 2]),
            'callback' => new Record\DirectRecord($expected['callback']),
            'lazy.hello' => new Record\LazyRecord(function (ContainerInterface $c) { return $c->get('test'); }),
            'lazy.goodbye' => new Record\LazyRecord(function () { return 'see ya!'; })
        ]);

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    public function testConstructWithNotAssociativeArray_ThrowsException()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->setup(['first' => 'ok', 2 => 'not ok'])->container();
    }

    public function testCallbacksCannotModifyRegistry()
    {
        $setup = $this->setup();
        $setup->push('lazyModifier', new Record\LazyRecord(function ($c) {
            $vars = get_object_vars($c);

            return isset($vars['records']);
        }));
        $this->assertFalse($setup->container()->get('lazyModifier'));
    }

    public function testOverwritingExistingKey_ThrowsException()
    {
        $setup = $this->setup(['test' => new Record\DirectRecord('foo')]);
        $this->expectException(Exception\InvalidIdException::class);
        $setup->push('test', new Record\DirectRecord('bar'));
    }

    public function testNumericId_ThrowsException()
    {
        $setup = $this->setup();
        $this->expectException(Exception\InvalidIdException::class);
        $setup->push('74', new Record\LazyRecord(function () { return 'foo'; }));
    }

    public function testEmptyFactoryId_ThrowsException()
    {
        $setup = $this->setup();
        $this->expectException(Exception\InvalidIdException::class);
        $setup->push('', new Record\DirectRecord(function () { return 'foo'; }));
    }

    public function testEmptyIdContainerCall_ThrowsException()
    {
        $container = $this->withBasicSettings()->container();
        $this->expectException(Exception\InvalidIdException::class);
        $container->has('');
    }

    public function testPullFromSetupUsingUndefinedToken_ThrowsException()
    {
        $setup = $this->withBasicSettings();
        $this->expectException(Exception\RecordNotFoundException::class);
        $setup->pull('undefined.value');
    }

    /**
     * @dataProvider inputScenarios
     *
     * @param $method
     * @param $id
     * @param $value
     * @param $result
     */
    public function testInputProxyMethods($method, $id, $value, $result)
    {
        $setup = $this->setup();
        $entry = new RecordEntry($id, $setup);
        $entry->{$method}($value);
        $container = $setup->container();
        $this->assertTrue($container->has($id));
        $this->assertSame($result ?: $value, $container->get($id));
    }

    public function inputScenarios()
    {
        return [
            ['value', 'direct', 'direct value', 'direct value'],
            ['lazy', 'lazy', function () { return 'lazy value'; }, 'lazy value'],
            ['record', 'directRecord', new Record\DirectRecord('direct value'), 'direct value'],
            ['record', 'lazyRecord', new Record\LazyRecord(function () { return 'lazy value'; }), 'lazy value']
        ];
    }

    public function testSetupContainer_ReturnsNewInstance()
    {
        $setup = $this->setup(['exists' => new Record\DirectRecord(true)]);
        $container1 = $setup->container();
        $setup->push('too.late', new Record\DirectRecord(true));
        $container2 = $setup->container();

        $this->assertTrue($container2->has('too.late'));
        $this->assertFalse($container1->has('too.late'));
    }

    public function testLazyRecord()
    {
        $container = $this->setup([
            'lazy.goodbye' => new Record\LazyRecord(function () {
                return new ExampleClass(function ($name) {
                    return 'Goodbye ' . $name;
                }, 'Shudd3r');
            })
        ])->container();

        $object = $container->get('lazy.goodbye');

        $this->assertSame('Goodbye Shudd3r', $object->beNice());
        $this->assertSame($object, $container->get('lazy.goodbye'));
    }

    public function testFactorRecord()
    {
        $setup = $this->setup([
            'name' => new Record\DirectRecord('Shudd3r'),
            'hello' => new Record\DirectRecord(function ($name) {
                return 'Hello ' . $name . '.';
            }),
            'polite' => new Record\DirectRecord('How are you?')
        ]);
        $entry = new RecordEntry('small.talk', $setup);
        $entry->factory(ExampleClass::class, 'hello', 'name');
        $container = $setup->container();

        $talk = 'Hello Shudd3r.';
        $this->assertSame($talk, $container->get('small.talk')->beNice());

        // Decorated record
        $entry = new RecordEntry('small.talk', $setup);
        $entry->factory(DecoratingExampleClass::class, 'small.talk', 'polite');
        $container = $setup->container();

        $talk = 'Hello Shudd3r. How are you?';
        $this->assertSame($talk, $container->get('small.talk')->beNice());

        // Decorated Again
        $setup->push('ask.football', new Record\DirectRecord('Have you seen that ridiculous display last night?'));
        $entry = new RecordEntry('small.talk', $setup);
        $entry->factory(DecoratingExampleClass::class, 'small.talk', 'ask.football');
        $container = $setup->container();

        $talk = 'Hello Shudd3r. How are you? Have you seen that ridiculous display last night?';
        $this->assertSame($talk, $container->get('small.talk')->beNice());
    }

    public function testFactoryForUndefinedDependecies_ThrowsException()
    {
        $entry = new RecordEntry('someClass', $this->withBasicSettings());
        $this->expectException(Exception\RecordNotFoundException::class);
        $entry->factory(ExampleClass::class, 'undefined.record', 'test');
    }

    protected function withBasicSettings()
    {
        $setup = $this->setup();
        $setup->push('test', new Record\DirectRecord('Hello World!'));
        $setup->push('lazy', new Record\LazyRecord(function () { return 'Lazy Foo'; }));

        return $setup;
    }
}
