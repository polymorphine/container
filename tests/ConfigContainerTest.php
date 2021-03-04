<?php declare(strict_types=1);

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
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;


class ConfigContainerTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container());
    }

    public function testContainer_has_ReturnsTrueForDefinedKeyPaths()
    {
        $container = $this->container();

        $this->assertFalse($container->has('missing'));
        $this->assertTrue($container->has('foo1'));
        $this->assertFalse($container->has('foo1.undefined'));
        $this->assertTrue($container->has('foo1.bar1'));
        $this->assertTrue($container->has('foo1.bar1.baz1'));
        $this->assertTrue($container->has('foo1.bar2'));
        $this->assertFalse($container->has('foo1.bar2.missing'));
        $this->assertTrue($container->has('foo2'));
        $this->assertTrue($container->has('foo2.bar'));
        $this->assertTrue($container->has('foo2.bar.baz'));
        $this->assertFalse($container->has('foo2.bar.baz.qux'));
        $this->assertTrue($container->has('foo3'));
        $this->assertFalse($container->has('foo3.more'));
    }

    public function testContainer_getForDefinedKeyPath_ReturnsValueDefinedWithThisPath()
    {
        $container = $this->container($config);

        $this->assertSame($config['foo1'], $container->get('foo1'));
        $this->assertSame($config['foo1']['bar1'], $container->get('foo1.bar1'));
        $this->assertSame($config['foo1']['bar1']['baz1'], $container->get('foo1.bar1.baz1'));
        $this->assertSame($config['foo1']['bar2'], $container->get('foo1.bar2'));
        $this->assertSame($config['foo2'], $container->get('foo2'));
        $this->assertSame($config['foo2']['bar'], $container->get('foo2.bar'));
        $this->assertSame($config['foo2']['bar']['baz'], $container->get('foo2.bar.baz'));
        $this->assertSame($config['foo3'], $container->get('foo3'));
    }

    /**
     * @dataProvider undefinedEntries
     *
     * @param string $id
     */
    public function testContainer_getUndefinedValue_ThrowsException(string $id)
    {
        $container = $this->container();
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get($id);
    }

    public function undefinedEntries(): array
    {
        return [['missing'], ['foo1.undefined'], ['foo1.bar2.missing'], ['foo2.bar.baz.qux'], ['foo3.more']];
    }

    private function container(?array &$config = []): ConfigContainer
    {
        if (!$config) {
            $config = [
                'foo1' => ['bar1' => ['baz1' => 'fooBarBazValue', 'baz2' => null], 'bar2' => 'fooBarValue'],
                'foo2' => ['bar' => ['baz' => '']],
                'foo3' => null
            ];
        }

        return new ConfigContainer($config);
    }
}
