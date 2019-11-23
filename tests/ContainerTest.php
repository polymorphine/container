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
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\Setup;
use Polymorphine\Container\Records;
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
        $this->assertInstanceOf(ContainerInterface::class, Setup::basic()->container());
        $this->assertInstanceOf(ContainerInterface::class, Setup::validated()->container());
        $this->assertInstanceOf(NotFoundExceptionInterface::class, new Exception\RecordNotFoundException());
        $this->assertInstanceOf(NotFoundExceptionInterface::class, new Exception\TrackedRecordNotFoundException());
        $this->assertInstanceOf(ContainerExceptionInterface::class, new Exception\CircularReferenceException());
    }

    public function testConfiguredRecordsAreAvailableFromContainer()
    {
        $setup = Setup::basic([
            'test' => new Record\ValueRecord('Hello World!'),
            'lazy' => new Record\CallbackRecord(function () { return 'Lazy Foo'; })
        ]);

        $setup->addRecords([
            'callback' => new Record\ValueRecord(function () {}),
            'foo'      => new Record\InstanceRecord(Example\ExampleClass::class, 'callback', 'test')
        ]);
        $container = $setup->container();

        $this->assertTrue($container->has('test') && $container->has('lazy'));
        $this->assertSame('Hello World!', $container->get('test'));
        $this->assertSame('Lazy Foo', $container->get('lazy'));
        $this->assertInstanceOf(Example\ExampleClass::class, $container->get('foo'));
    }

    public function testClosuresForLazyLoadedValuesCanAccessContainer()
    {
        $setup = Setup::basic([
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
        $container = Setup::basic($records, $config)->container();

        $this->assertTrue($container->has('null'));
        $this->assertTrue($container->has('false'));
        $this->assertTrue($container->has('cfg.null'));
        $this->assertTrue($container->has('cfg.false'));
    }

    public function testInvalidContainerIdTypeIsCastedToString()
    {
        $container = Setup::basic(['23' => new Record\ValueRecord('Michael Jordan!')])->container();
        $this->assertSame('Michael Jordan!', $container->get(23));
    }

    public function testAccessingAbsentIdFromContainer_ThrowsException()
    {
        $container = Setup::basic()->container();
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

        $container = Setup::basic($records)->container();

        foreach ($expected as $key => $value) {
            $this->assertTrue($container->has($key), 'Failed for key: ' . $key);
            $this->assertSame($value, $container->get($key), 'Failed for key: ' . $key);
        }
    }

    public function testCallbacksCannotModifyRegistry()
    {
        $setup = Setup::basic();
        $setup->entry('lazyModifier')->callback(function ($c) {
            $vars = get_object_vars($c);

            return isset($vars['records']);
        });
        $this->assertFalse($setup->container()->get('lazyModifier'));
    }

    public function testSetup_RecordsCanBeAdded()
    {
        $setup = Setup::basic();
        $setup->addRecord('foo', new Record\ValueRecord('bar'));
        $this->assertSame('bar', $setup->container()->get('foo'));

        $setup = Setup::validated();
        $setup->addRecord('foo', new Record\ValueRecord('bar'));
        $this->assertSame('bar', $setup->container()->get('foo'));
    }

    public function testSetup_RecordsCanBeReplaced()
    {
        $setup = Setup::basic(['foo' => new Record\ValueRecord('bar')]);
        $setup->replaceRecord('foo', new Record\ValueRecord('baz'));
        $this->assertSame('baz', $setup->container()->get('foo'));

        $setup = Setup::validated(['foo' => new Record\ValueRecord('bar')]);
        $setup->replaceRecord('foo', new Record\ValueRecord('baz'));
        $this->assertSame('baz', $setup->container()->get('foo'));
    }

    public function testSetup_ContainersCanBeAdded()
    {
        $setup = Setup::basic();
        $setup->addContainer('foo', $container = new ConfigContainer([]));
        $this->assertSame($container, $setup->container()->get('foo'));

        $setup = Setup::validated();
        $setup->addContainer('foo', $container = new ConfigContainer([]));
        $this->assertSame($container, $setup->container()->get('foo'));
    }

    public function testSetup_ContainersCanBeReplaced()
    {
        $setup = Setup::basic([], ['foo' => new ConfigContainer([])]);
        $setup->replaceContainer('foo', $container = new ConfigContainer([]));
        $this->assertSame($container, $setup->container()->get('foo'));

        $setup = Setup::validated([], ['foo' => new ConfigContainer([])]);
        $setup->replaceContainer('foo', $container = new ConfigContainer([]));
        $this->assertSame($container, $setup->container()->get('foo'));
    }

    public function testValidatedSetup_ReplaceUndefinedRecord_ThrowsException()
    {
        $setup = Setup::validated();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->replaceRecord('foo', new Record\ValueRecord('bar'));
    }

    public function testValidatedSetup_ReplaceUndefinedContainer_ThrowsException()
    {
        $setup = Setup::validated();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->replaceContainer('foo', new ConfigContainer([]));
    }

    public function testValidatedSetup_AddExistingRecord_ThrowsException()
    {
        $setup = Setup::validated(['test' => new Record\ValueRecord('bar')]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecord('test', new Record\ValueRecord('baz'));
    }

    public function testValidatedSetup_AddExistingContainer_ThrowsException()
    {
        $setup = Setup::validated([], ['foo' => new ConfigContainer([])]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addContainer('foo', new ConfigContainer([]));
    }

    public function testAddingRecordsArrayWithExistingRecord_ThrowsException()
    {
        $setup = Setup::validated(['exists' => new Record\ValueRecord('something')]);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->addRecords(['notExists' => new Record\ValueRecord('foo'), 'exists' => new Record\ValueRecord('bar')]);
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
        $setup = Setup::basic();
        $setup->entry($id)->{$method}($value);
        $container = $setup->container();

        $this->assertTrue($container->has($id));
        $this->assertSame($result ?: $value, $container->get($id));
    }

    public function inputScenarios()
    {
        return [
            ['value', 'direct', 'direct value', 'direct value'],
            ['callback', 'lazy', function () { return 'lazy value'; }, 'lazy value'],
            ['record', 'directRecord', new Record\ValueRecord('direct value'), 'direct value'],
            ['record', 'lazyRecord', new Record\CallbackRecord(function () { return 'lazy value'; }), 'lazy value']
        ];
    }

    public function testBuildConfigContainerWithSetup()
    {
        $setup = Setup::basic();
        $setup->entry('cfg')->container(new configContainer(['test' => 'value']));
        $container = $setup->container();

        $this->assertTrue($container->has('cfg.test'));
        $this->assertSame('value', $container->get('cfg.test'));
    }

    public function testSetupContainer_ReturnsNewInstanceOfContainer()
    {
        $config    = ['env' => new ConfigContainer(['config' => 'value'])];
        $setup     = Setup::basic(['exists' => new Record\ValueRecord(true)], $config);
        $container = $setup->container();
        $setup->entry('not.too.late')->value(true);

        $this->assertNotSame($newContainer = $setup->container(), $container);
        $this->assertTrue($newContainer->has('exists'));
        $this->assertTrue($newContainer->has('not.too.late'));
        $this->assertFalse($container->has('not.too.late'));
    }

    public function testCallbackRecord()
    {
        $setup = Setup::basic();
        $setup->entry('lazy.goodbye')->callback(function () {
            return new Example\ExampleClass(function ($name) {
                return 'Goodbye ' . $name;
            }, 'Shudd3r');
        });
        $container = $setup->container();
        $object    = $container->get('lazy.goodbye');

        $this->assertSame('Goodbye Shudd3r', $object->beNice());
        $this->assertSame($object, $container->get('lazy.goodbye'));
    }

    public function testInstanceRecord()
    {
        $config = [
            'foo' => new ConfigContainer(['env' => ['name' => 'Shudd3r', 'polite' => 'How are you?']]),
            'cfg' => new ConfigContainer(['hello' => function ($name) { return 'Hello ' . $name . '.'; }])
        ];

        $setup = Setup::basic([], $config);
        $setup->entry('small.talk')->instance(Example\ExampleClass::class, 'cfg.hello', 'foo.env.name');
        $container = $setup->container();

        $this->assertSame('Hello Shudd3r.', $container->get('small.talk')->beNice());
    }

    public function testComposedInstanceRecord()
    {
        $wrapped = new Record\ValueRecord('foo-bar');
        $wrappers = [
            [Example\ExampleClass::class, ['callback', 'composed']],
            [Example\DecoratingExampleClass::class, ['composed', 'text']],
            [Example\DecoratingExampleClass::class, ['composed', 'another']]
        ];
        $composed  = new Record\ComposedInstanceRecord('composed', $wrapped, $wrappers);
        $container = new RecordContainer(new Records($this->components(['composed' => $composed])));

        $expected = 'callback(foo-bar) ...and some text ...and another';
        $this->assertSame($expected, $container->get('composed')->beNice());
    }

    public function testSetupWrapMethod()
    {
        $setup = Setup::validated($this->components(['composed' => new Record\ValueRecord('foo-bar')]));

        $setup->decorate('composed')
              ->with(Example\ExampleClass::class, 'callback', 'composed')
              ->with(Example\DecoratingExampleClass::class, 'composed', 'text')
              ->with(Example\DecoratingExampleClass::class, 'composed', 'another')
              ->compose();

        $expected = 'callback(foo-bar) ...and some text ...and another';
        $this->assertSame($expected, $setup->container()->get('composed')->beNice());
    }

    public function testEntryComposeMethod()
    {
        $setup = Setup::validated($this->components(['name' => new Record\ValueRecord('foo-bar')]));

        $setup->entry('composed')
              ->wrappedInstance(Example\ExampleClass::class, 'callback', 'name')
              ->with(Example\DecoratingExampleClass::class, 'composed', 'text')
              ->with(Example\DecoratingExampleClass::class, 'composed', 'another')
              ->compose();

        $expected = 'callback(foo-bar) ...and some text ...and another';
        $this->assertSame($expected, $setup->container()->get('composed')->beNice());
    }

    public function testSetupWrapUndefinedRecord_ThrowsException()
    {
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        Setup::basic()->decorate('undefined');
    }

    public function testWrapWithoutWrappedDependency_ThrowsException()
    {
        $setup = Setup::validated([
            'composed' => new Record\ValueRecord('foo-bar'),
            'dummy'    => new Record\ValueRecord('baz')
        ]);

        $wrapper = $setup->decorate('composed');
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $wrapper->with(Example\ExampleClass::class, 'callback', 'dummy');
    }

    public function testProductRecord()
    {
        $config['foo'] = new ConfigContainer(['one' => 'first', 'two' => 'second', 'three' => 'third']);
        $setup = Setup::basic([], $config);
        $setup->entry('factory')->value(new Example\Factory());
        $setup->entry('product')->product('factory', 'create', 'foo.one', 'foo.two', 'foo.three');
        $container = $setup->container();

        $this->assertSame('first,second,third', $container->get('product'));
    }

    public function testConfigsCanBeReadWithPath()
    {
        $data = ['key1' => ['nested' => ['double' => 'nested value']], 'key2' => 'value2'];
        $config['foo'] = new ConfigContainer(['env' => $data]);
        $container = Setup::basic([], $config)->container();

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
        $container = Setup::basic([], $config)->container();

        $this->assertFalse($container->has($undefinedPath));
        $this->expectException(Exception\RecordNotFoundException::class);
        $container->get($undefinedPath);
    }

    public function undefinedPaths(): array
    {
        return [['foo.key1.nested.value'], ['foo.key1.something'], ['foo.whatever'], ['notEnv']];
    }

    public function testContainerIdWithIdSeparator_SecureSetupThrowsException()
    {
        $setup = Setup::validated();
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->entry('cfg.data')->container(new ConfigContainer([]));
    }

    public function testRecordIdUsedAsContainerId_SecureSetupThrowsException()
    {
        $setup = Setup::validated();
        $setup->entry('prefix')->container(new ConfigContainer([]));
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->entry('prefix.foo')->value(true);
    }

    public function testContainerIdUsedAsRecordPrefix_SecureSetupThrowsException()
    {
        $setup = Setup::validated();
        $setup->entry('prefix.foo')->value(true);
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->entry('prefix')->container(new ConfigContainer([]));
    }

    public function testRecordPrefixUsedAsContainerId_SecureSetupThrowsException()
    {
        $setup = Setup::validated();
        $setup->entry('prefix')->container(new ConfigContainer([]));
        $this->expectException(Setup\Exception\IntegrityConstraintException::class);
        $setup->entry('prefix.foo')->value(true);
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
        Setup::validated($records, $config);
    }

    public function invalidSetupConstructorParams()
    {
        $record    = new Record\ValueRecord(true);
        $container = new ConfigContainer([]);

        return [
            [['foo.bar' => $record], ['foo' => $container], Setup\Exception\IntegrityConstraintException::class],
            [['foo' => $record], ['foo' => $container], Setup\Exception\IntegrityConstraintException::class],
            [['foo' => true], ['bar' => $container], Setup\Exception\InvalidTypeException::class],
            [['foo' => $record], ['bar' => []], Setup\Exception\InvalidTypeException::class]
        ];
    }

    public function testDirectCircularCall_ThrowsException()
    {
        $setup = Setup::validated();
        $setup->entry('ref.self')->callback(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $container = $setup->container();
        $this->expectException(Exception\CircularReferenceException::class);
        $this->expectExceptionMessage('ref.self->ref.self');
        $container->get('ref.self');
    }

    public function testIndirectCircularCall_ThrowsException()
    {
        $setup = Setup::validated();
        $setup->entry('ref')->callback(function (ContainerInterface $c) {
            return $c->get('ref.self');
        });
        $setup->entry('ref.self')->callback(function (ContainerInterface $c) {
            return $c->get('ref.dependency');
        });
        $setup->entry('ref.dependency')->callback(function (ContainerInterface $c) {
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
        $setup = Setup::validated([], $config);
        $setup->entry('ref')->callback(function (ContainerInterface $c) {
            return $c->get('ref.multiple') . ':' . $c->get('ref.multiple') . ':' . $c->get('foo.config');
        });
        $setup->entry('ref.multiple')->value('Test');
        $this->assertSame('Test:Test:value', $setup->container()->get('ref'));
    }

    public function testMultipleIndirectCallsAreNotCircular()
    {
        $setup = Setup::validated();
        $setup->entry('ref')->callback(function (ContainerInterface $c) {
            return $c->get('function')($c);
        });
        $setup->entry('function')->callback(function (ContainerInterface $c) {
            return function (ContainerInterface $test) use ($c) {
                return $c->get('ref.multiple') . ':' . $test->get('ref.multiple');
            };
        });
        $setup->entry('ref.multiple')->value('Test');
        $this->assertSame('Test:Test', $setup->container()->get('ref'));
    }

    public function testTrackingStopsAfterItemIsReturned()
    {
        $setup = Setup::validated();
        $setup->entry('ref')->callback(function (ContainerInterface $c) {
            return $c;
        });
        $container = $setup->container();

        $trackedContainer = $container->get('ref');
        $this->assertSame($trackedContainer, $trackedContainer->get('ref'));
    }

    public function testCallStackIsAddedToContainerExceptionMessage()
    {
        $setup = Setup::validated([], ['config' => new ConfigContainer(['foo' => 'bar'])]);
        $setup->entry('A')->value(function () {});
        $setup->entry('B')->callback(function (ContainerInterface $c) {
            return new Example\ExampleClass($c->get('A'), $c->get('undefined'));
        });
        $setup->entry('C')->instance(Example\DecoratingExampleClass::class, 'B', '.config');

        $container = $setup->container();
        $this->expectExceptionMessage('C->B->undefined->...');
        $container->get('C');
    }

    private function components(array $records = []): array
    {
        return $records + [
            'callback' => new Record\ValueRecord(function (string $name) { return "callback($name)"; }),
            'text'     => new Record\ValueRecord('...and some text'),
            'another'  => new Record\ValueRecord('...and another')
        ];
    }
}
