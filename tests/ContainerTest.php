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
use Polymorphine\Container\Setup;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Exception;
use Polymorphine\Container\Tests\Fixtures\Example;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;


class ContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ContainerInterface::class, Setup::withData()->container());
        $this->assertInstanceOf(ContainerExceptionInterface::class, new Exception\InvalidTypeException());
        $this->assertInstanceOf(ContainerExceptionInterface::class, new Exception\InvalidIdException());
        $this->assertInstanceOf(NotFoundExceptionInterface::class, new Exception\RecordNotFoundException());
        $this->assertInstanceOf(NotFoundExceptionInterface::class, new Exception\TrackedRecordNotFoundException());
        $this->assertInstanceOf(ContainerExceptionInterface::class, new Exception\CircularReferenceException());
    }

    public function testConfiguredRecordsAreAvailableFromContainer()
    {
        $setup = Setup::withData([
            'test' => new Record\ValueRecord('Hello World!'),
            'lazy' => new Record\CallbackRecord(function () { return 'Lazy Foo'; })
        ]);

        $setup->records([
            'callback' => new Record\ValueRecord(function () {}),
            'foo'      => new Record\ComposeRecord(Example\ExampleClass::class, 'callback', 'test')
        ]);
        $container = $setup->container();

        $this->assertTrue($container->has('test') && $container->has('lazy'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
        $this->assertInstanceOf(Example\ExampleClass::class, $container->get('foo'));
    }

    public function testClosuresForLazyLoadedValuesCanAccessContainer()
    {
        $setup = Setup::withData([
            'test' => new Record\ValueRecord('Hello World!'),
            'lazy' => new Record\CallbackRecord(function () { return 'Lazy Foo'; }),
            'bar' => new Record\CallbackRecord(function (ContainerInterface $c) {
                return substr($c->get('test'), 0, 6) . $c->get('lazy') . '!';
            })
        ]);
        $container = $setup->container();

        $this->assertSame('Hello Lazy Foo!', $container->get('bar'));
    }

    public function testGivenContainerWithEmptyValues_HasMethodReturnsTrue()
    {
        $records = [
            'null'  => new Record\ValueRecord(null),
            'false' => new Record\ValueRecord(false)
        ];
        $config['cfg'] = new ConfigContainer([
            'null'  => null,
            'false' => false
        ]);
        $container = Setup::withData($records, $config)->container();

        $this->assertTrue($container->has('null'));
        $this->assertTrue($container->has('false'));
        $this->assertTrue($container->has('cfg.null'));
        $this->assertTrue($container->has('cfg.false'));
    }

    public function testInvalidContainerIdTypeIsCastedToString()
    {
        $container = Setup::withData(['23' => new Record\ValueRecord('Michael Jordan!')])->container();
        $this->assertSame('Michael Jordan!', $container->get(23));
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException()
    {
        $container = Setup::withData()->container();
        $this->expectException(Exception\RecordNotFoundException::class);
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
            'lazy.goodbye'    => 'see ya!',
            '2643'            => 'numeric id!',
            ''                => 'empty id?!'
        ];

        $records = [
            'test'            => new Record\ValueRecord('Hello World!'),
            'category.first'  => new Record\ValueRecord('one'),
            'category.second' => new Record\ValueRecord('two'),
            'array'           => new Record\ValueRecord([1, 2, 3]),
            'assoc'           => new Record\ValueRecord(['first' => 1, 'second' => 2]),
            'callback'        => new Record\ValueRecord($expected['callback']),
            'lazy.hello'      => new Record\CallbackRecord(function (ContainerInterface $c) { return $c->get('test'); }),
            'lazy.goodbye'    => new Record\CallbackRecord(function () { return 'see ya!'; }),
            '2643'            => new Record\ValueRecord('numeric id!'),
            ''                => new Record\ValueRecord('empty id?!')
        ];

        $container = Setup::withData($records)->container();

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    public function testCallbacksCannotModifyRegistry()
    {
        $setup = Setup::withData();
        $setup->entry('lazyModifier')->invoke(function ($c) {
            $vars = get_object_vars($c);

            return isset($vars['records']);
        });
        $this->assertFalse($setup->container()->get('lazyModifier'));
    }

    public function testOverwritingExistingKey_ThrowsException()
    {
        $setup = Setup::withData();
        $setup->entry('test')->set('foo');
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('test')->set('bar');
    }

    public function testAddingRecordsArrayWithExistingRecord_ThrowsException()
    {
        $setup = Setup::withData(['exists' => new Record\ValueRecord('something')]);
        $this->expectException(Exception\InvalidIdException::class);
        $setup->records(['notExists' => new Record\ValueRecord('foo'), 'exists' => new Record\ValueRecord('bar')]);
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
        $setup = Setup::withData();
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

    public function testBuildConfigContainerWithSetup()
    {
        $setup = Setup::withData([], []);
        $setup->entry('cfg')->container(new configContainer(['test' => 'value']));
        $container = $setup->container();

        $this->assertTrue($container->has('cfg.test'));
        $this->assertSame('value', $container->get('cfg.test'));
    }

    public function testOverwritingContainerId_ThrowsException()
    {
        $setup = Setup::withData();
        $setup->entry('data')->container(new ConfigContainer([]));
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('data')->container(new ConfigContainer([]));
    }

    public function testSetupContainer_ReturnsNewInstanceOfContainer()
    {
        $config    = ['env' => new ConfigContainer(['config' => 'value'])];
        $setup     = Setup::withData(['exists' => new Record\ValueRecord(true)], $config);
        $container = $setup->container();
        $setup->entry('not.too.late')->set(true);

        $this->assertNotSame($newContainer = $setup->container(), $container);
        $this->assertTrue($newContainer->has('exists'));
        $this->assertTrue($newContainer->has('not.too.late'));
        $this->assertFalse($container->has('not.too.late'));
    }

    public function testCallbackRecord()
    {
        $setup = Setup::withData();
        $setup->entry('lazy.goodbye')->invoke(function () {
            return new Example\ExampleClass(function ($name) {
                return 'Goodbye ' . $name;
            }, 'Shudd3r');
        });
        $container = $setup->container();
        $object    = $container->get('lazy.goodbye');

        $this->assertSame('Goodbye Shudd3r', $object->beNice());
        $this->assertSame($object, $container->get('lazy.goodbye'));
    }

    public function testCompositeRecord()
    {
        $config = [
            'foo' => new ConfigContainer(['env' => ['name' => 'Shudd3r', 'polite' => 'How are you?']]),
            'cfg' => new ConfigContainer(['hello' => function ($name) { return 'Hello ' . $name . '.'; }])
        ];

        $setup = Setup::withData([], $config);
        $setup->entry('small.talk')->compose(Example\ExampleClass::class, 'cfg.hello', 'foo.env.name');
        $container = $setup->container();

        $expect = 'Hello Shudd3r.';
        $this->assertSame($expect, $container->get('small.talk')->beNice());

        // Decorated record
        $setup = Setup::withData([], $config);
        $setup->entry('small.talk')->compose(Example\ExampleClass::class, 'cfg.hello', 'foo.env.name');
        $setup->entry('small.talk')->compose(Example\DecoratingExampleClass::class, 'small.talk', 'foo.env.polite');
        $container = $setup->container();

        $expect = 'Hello Shudd3r. How are you?';
        $this->assertSame($expect, $container->get('small.talk')->beNice());

        // Decorated Again
        $setup = Setup::withData([], $config);
        $setup->entry('ask.football')->set('Have you seen that ridiculous display last night?');
        $setup->entry('small.talk')->compose(Example\ExampleClass::class, 'cfg.hello', 'foo.env.name');
        $setup->entry('small.talk')->compose(Example\DecoratingExampleClass::class, 'small.talk', 'foo.env.polite');
        $setup->entry('small.talk')->compose(Example\DecoratingExampleClass::class, 'small.talk', 'ask.football');
        $container = $setup->container();

        $expect = 'Hello Shudd3r. How are you? Have you seen that ridiculous display last night?';
        $this->assertSame($expect, $container->get('small.talk')->beNice());
    }

    public function testCompositeRecordForUndefinedDecoratedDependency_ThrowsException()
    {
        $builder = Setup::withData();
        $builder->entry('someClass')->compose(Example\ExampleClass::class, 'not.exists', 'doesnt.matter');
        $entry = $builder->entry('decorating.undefined.id');
        $this->expectException(Exception\RecordNotFoundException::class);
        $entry->compose(Example\ExampleClass::class, 'undefined.record', 'decorating.undefined.id');
    }

    public function testCreateMethodRecord()
    {
        $config['foo'] = new ConfigContainer(['one' => 'first', 'two' => 'second', 'three' => 'third']);
        $setup = Setup::withData([], $config);
        $setup->entry('factory')->set(new Example\Factory());
        $setup->entry('product')->create('factory', 'create', 'foo.one', 'foo.two', 'foo.three');
        $container = $setup->container();

        $this->assertSame('first,second,third', $container->get('product'));
    }

    public function testConfigsCanBeReadWithPath()
    {
        $data = ['key1' => ['nested' => ['double' => 'nested value']], 'key2' => 'value2'];
        $config['foo'] = new ConfigContainer(['env' => $data]);
        $container = Setup::withData([], $config)->container();

        $this->assertSame($data['key1']['nested']['double'], $container->get('foo.env.key1.nested.double'));
        $this->assertSame($data['key1']['nested'], $container->get('foo.env.key1.nested'));
        $this->assertSame($data['key2'], $container->get('foo.env.key2'));
        $this->assertSame($data['key1'], $container->get('foo.env.key1'));
        $this->assertSame($data, $container->get('foo.env'));
        $this->assertSame($config['foo'], $container->get('foo'));

        $this->assertTrue($container->has('foo.env.key1.nested.double'));
        $this->assertTrue($container->has('foo.env.key1.nested'));
        $this->assertTrue($container->has('foo.env.key2'));
        $this->assertTrue($container->has('foo.env.key1'));
        $this->assertTrue($container->has('foo.env'));
        $this->assertTrue($container->has('foo'));
    }

    /**
     * @dataProvider undefinedPaths
     *
     * @param string $undefinedPath
     */
    public function testGetMissingConfigRecord_ThrowsException(string $undefinedPath)
    {
        $config['foo'] = new ConfigContainer(['key1' => ['nested' => ['double' => 'nested value']], 'key2' => 'value2']);
        $container = Setup::withData([], $config)->container();

        $this->assertFalse($container->has($undefinedPath));
        $this->expectException(Exception\RecordNotFoundException::class);
        $container->get($undefinedPath);
    }

    public function undefinedPaths(): array
    {
        return [['foo.key1.nested.value'], ['foo.key1.something'], ['foo.whatever'], ['notEnv']];
    }

    public function testInstantiatingSecureSetup()
    {
        $this->assertEquals(Setup::secure(), new Setup(new Setup\ValidatedCollection()));
        $this->assertEquals(Setup::secure(), Setup::withData([], [], true));
    }

    public function testContainerIdWithIdSeparator_SecureSetupThrowsException()
    {
        $setup = Setup::withData([], [], true);
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('cfg.data')->container(new ConfigContainer([]));
    }

    public function testRecordIdUsedAsContainerId_SecureSetupThrowsException()
    {
        $setup = Setup::withData([], [], true);
        $setup->entry('prefix')->container(new ConfigContainer([]));
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('prefix.foo')->set(true);
    }

    public function testContainerIdUsedAsRecordPrefix_SecureSetupThrowsException()
    {
        $setup = Setup::withData([], [], true);
        $setup->entry('prefix.foo')->set(true);
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('prefix')->container(new ConfigContainer([]));
    }

    public function testRecordPrefixUsedAsContainerId_SecureSetupThrowsException()
    {
        $setup = Setup::withData([], [], true);
        $setup->entry('prefix')->container(new ConfigContainer([]));
        $this->expectException(Exception\InvalidIdException::class);
        $setup->entry('prefix.foo')->set(true);
    }

    /**
     * @dataProvider invalidSetupConstructorParams
     *
     * @param array  $records
     * @param array  $config
     * @param string $exception
     */
    public function testSetupWithInvalidConstructorStructures_SecureSetupThrowsException(
        array $records,
        array $config,
        string $exception
    ) {
        $this->expectException($exception);
        Setup::withData($records, $config, true);
    }

    public function invalidSetupConstructorParams()
    {
        $record    = new Record\ValueRecord(true);
        $container = new ConfigContainer([]);

        return [
            [['foo.bar' => $record], ['foo' => $container], Exception\InvalidIdException::class],
            [['foo' => $record], ['foo' => $container], Exception\InvalidIdException::class],
            [['foo' => true], ['bar' => $container], Exception\InvalidTypeException::class],
            [['foo' => $record], ['bar' => []], Exception\InvalidTypeException::class]
        ];
    }

    public function testDirectCircularCall_ThrowsException()
    {
        $setup = Setup::withData([], [], true);
        $setup->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('ref.self->ref.self');
        $container->get('ref.self');
    }

    public function testIndirectCircularCall_ThrowsException()
    {
        $setup = Setup::withData([], [], true);
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $setup->entry('ref.self')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.dependency');
        });
        $setup->entry('ref.dependency')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('ref->ref.self->ref.dependency->ref.self');
        $container->get('ref');
    }

    public function testMultipleCallsAreNotCircular()
    {
        $config['foo'] = new ConfigContainer(['config' => 'value']);
        $setup = Setup::withData([], $config, true);
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c->get('ref.multiple') . ':' . $c->get('ref.multiple') . ':' . $c->get('foo.config');
        });
        $setup->entry('ref.multiple')->set('Test');
        $this->assertSame('Test:Test:value', $setup->container()->get('ref'));
    }

    public function testMultipleIndirectCallsAreNotCircular()
    {
        $setup = Setup::withData([], [], true);
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

    public function testTrackingStopsAfterItemIsReturned()
    {
        $setup = Setup::withData([], [], true);
        $setup->entry('ref')->invoke(function (ContainerInterface $c) {
            return $c;
        });
        $container = $setup->container();

        $trackedContainer = $container->get('ref');
        $this->assertSame($trackedContainer, $trackedContainer->get('ref'));
    }

    public function testCallStackIsAddedToContainerExceptionMessage()
    {
        $setup = Setup::withData([], ['config' => new ConfigContainer(['foo' => 'bar'])], true);
        $setup->entry('A')->set(function () {});
        $setup->entry('B')->invoke(function (ContainerInterface $c) {
            return new Example\ExampleClass($c->get('A'), $c->get('undefined'));
        });
        $setup->entry('C')->compose(Example\DecoratingExampleClass::class, 'B', '.config');

        $container = $setup->container();
        $this->expectExceptionMessage('C->B->undefined->...');
        $container->get('C');
    }
}
