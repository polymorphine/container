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
use Polymorphine\Container\RecordCollection;
use Polymorphine\Container\Exception;
use Polymorphine\Container\Tests\Fixtures\Example;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class ContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ContainerInterface::class, Container::fromRecordsArray([]));
        $this->assertInstanceOf(ContainerInterface::class, $this->factory()->container());
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
        $setup->entry('bar')->invoke(function (ContainerInterface $c) {
            return substr($c->get('test'), 0, 6) . $c->get('lazy') . '!';
        });
        $container = $setup->container();

        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testGivenContainerWithEmptyValues_HasMethodReturnsTrue()
    {
        $container = new Container(new RecordCollection([
            'null'  => new Record\ValueRecord(null),
            'false' => new Record\ValueRecord(false)
        ]));

        $this->assertTrue($container->has('null'));
        $this->assertTrue($container->has('false'));
    }

    public function testInvalidContainerIdType_ThrowsException()
    {
        $container = $this->factory()->container();
        $this->expectException(ContainerExceptionInterface::class);
        $container->get(23);
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException()
    {
        $container = $this->factory()->container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get('not.set');
    }

    public function testRegistryConstructorRecordsAreAvailableFromContainer()
    {
        $expected = [
            'test'            => 'Hello World!',
            'category.first'  => 'one',
            'category.second' => 'two',
            'array'           => [1, 2, 3],
            'assoc'           => ['first' => 1, 'second' => 2],
            'callback'        => function () { return 'first'; },
            'lazy.hello'      => 'Hello World!',
            'lazy.goodbye'    => 'see ya!'
        ];

        $container = new Container(new RecordCollection([
            'test'            => new Record\ValueRecord('Hello World!'),
            'category.first'  => new Record\ValueRecord('one'),
            'category.second' => new Record\ValueRecord('two'),
            'array'           => new Record\ValueRecord([1, 2, 3]),
            'assoc'           => new Record\ValueRecord(['first' => 1, 'second' => 2]),
            'callback'        => new Record\ValueRecord($expected['callback']),
            'lazy.hello'      => new Record\CallbackRecord(function (ContainerInterface $c) { return $c->get('test'); }),
            'lazy.goodbye'    => new Record\CallbackRecord(function () { return 'see ya!'; })
        ]));

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    public function testConstructWithNotAssociativeArray_ThrowsException()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->factory(['first' => 'ok', 2 => 'not ok']);
    }

    public function testCallbacksCannotModifyRegistry()
    {
        $setup = $this->factory();
        $setup->entry('lazyModifier')->invoke(function ($c) {
            $vars = get_object_vars($c);

            return isset($vars['records']);
        });
        $this->assertFalse($setup->container()->get('lazyModifier'));
    }

    public function testOverwritingExistingKey_ThrowsException()
    {
        $setup = $this->factory(['test' => new Record\ValueRecord('foo')]);
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('test')->set('bar');
    }

    public function testNumericId_ThrowsException()
    {
        $setup = $this->factory();
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('74')->invoke(function () { return 'foo'; });
    }

    public function testEmptyFactoryId_ThrowsException()
    {
        $setup = $this->factory();
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('')->set(function () { return 'foo'; });
    }

    public function testEmptyIdContainerCall_ThrowsException()
    {
        $container = $this->withBasicSettings()->container();
        $this->expectException(Exception\InvalidIdException::class);
        $container->get('');
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
        $setup = $this->factory();
        $setup->entry($id)->{$method}($value);
        $container = $setup->container();

        $this->assertTrue($container->has($id));
        $this->assertSame($result ?: $value, $container->get($id));
    }

    public function inputScenarios()
    {
        return [
            ['set', 'direct', 'direct value', 'direct value'],
            ['invoke', 'lazy', function () { return 'lazy value'; }, 'lazy value'],
            ['useRecord', 'directRecord', new Record\ValueRecord('direct value'), 'direct value'],
            ['useRecord', 'lazyRecord', new Record\CallbackRecord(function () { return 'lazy value'; }), 'lazy value']
        ];
    }

    public function testSetupContainer_ReturnsSameInstanceOfContainer()
    {
        $setup      = $this->factory(['exists' => new Record\ValueRecord(true)]);
        $container1 = $setup->container();
        $setup->entry('not.too.late')->set(true);
        $container2 = $setup->container();

        $this->assertSame($container1, $container2);
    }

    public function testSetupContainerExistsCheck()
    {
        $setup = $this->factory(['defined' => new Record\ValueRecord(true)]);

        $this->assertTrue($setup->exists('defined'));
        $this->assertFalse($setup->exists('undefined'));
    }

    public function testCallbackRecord()
    {
        $container = $this->factory([
            'lazy.goodbye' => new Record\CallbackRecord(function () {
                return new Example\ExampleClass(function ($name) {
                    return 'Goodbye ' . $name;
                }, 'Shudd3r');
            })
        ])->container();

        $object = $container->get('lazy.goodbye');

        $this->assertSame('Goodbye Shudd3r', $object->beNice());
        $this->assertSame($object, $container->get('lazy.goodbye'));
    }

    public function testCompositeRecord()
    {
        $records = [
            'name'   => new Record\ValueRecord('Shudd3r'),
            'hello'  => new Record\ValueRecord(function ($name) { return 'Hello ' . $name . '.'; }),
            'polite' => new Record\ValueRecord('How are you?')
        ];

        $setup = $this->factory($records);
        $setup->entry('small.talk')->compose(Example\ExampleClass::class, 'hello', 'name');
        $container = $setup->container();

        $expect = 'Hello Shudd3r.';
        $this->assertSame($expect, $container->get('small.talk')->beNice());

        // Decorated record
        $setup = $this->factory($records);
        $setup->entry('small.talk')->compose(Example\ExampleClass::class, 'hello', 'name');
        $setup->entry('small.talk')->compose(Example\DecoratingExampleClass::class, 'small.talk', 'polite');
        $container = $setup->container();

        $expect = 'Hello Shudd3r. How are you?';
        $this->assertSame($expect, $container->get('small.talk')->beNice());

        // Decorated Again
        $setup = $this->factory($records);
        $setup->entry('ask.football')->set('Have you seen that ridiculous display last night?');
        $setup->entry('small.talk')->compose(Example\ExampleClass::class, 'hello', 'name');
        $setup->entry('small.talk')->compose(Example\DecoratingExampleClass::class, 'small.talk', 'polite');
        $setup->entry('small.talk')->compose(Example\DecoratingExampleClass::class, 'small.talk', 'ask.football');
        $container = $setup->container();

        $expect = 'Hello Shudd3r. How are you? Have you seen that ridiculous display last night?';
        $this->assertSame($expect, $container->get('small.talk')->beNice());
    }

    public function testCompositeForUndefinedDependencies_ThrowsException()
    {
        $entry = $this->withBasicSettings()->entry('someClass');
        $this->expectException(Exception\RecordNotFoundException::class);
        $entry->compose(Example\ExampleClass::class, 'undefined.record', 'test');
    }

    public function testDirectCircularCall_ThrowsException()
    {
        $factory = $this->factory();
        $factory->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $factory->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $container->get('ref.self');
    }

    public function testIndirectCircularCall_ThrowsException()
    {
        $factory = $this->factory();
        $factory->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $factory->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->has('ref.dependency') ? $c->get('ref.dependency') : null;
        });
        $factory->entry('ref.dependency')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $factory->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $container->get('ref');
    }

    protected function factory(array $data = [])
    {
        return new ContainerSetup($data);
    }

    protected function withBasicSettings()
    {
        $factory = $this->factory([
            'test' => new Record\ValueRecord('Hello World!'),
            'lazy' => new Record\CallbackRecord(function () { return 'Lazy Foo'; })
        ]);

        return $factory;
    }
}
