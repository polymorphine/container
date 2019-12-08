<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Doubles;

use Polymorphine\Container\Records;
use Polymorphine\Container\Setup\Build;
use Polymorphine\Container\Setup\Entry\ReplaceEntry;
use Polymorphine\Container\Setup\Entry\Wrapper;
use Psr\Container\ContainerInterface;


class MockedBuild extends Build
{
    public $container;
    public $wrapper;

    public $setRecords    = [];
    public $setContainers = [];

    private $defined;

    public static function defined(): self
    {
        $build = new self();
        $build->defined = true;
        return $build;
    }

    public static function undefined(): self
    {
        $build = new self();
        $build->defined = false;
        return $build;
    }

    public function container(): ContainerInterface
    {
        return $this->container = new FakeContainer();
    }

    public function has(string $id): bool
    {
        return $this->defined ?? parent::has($id);
    }

    public function decorator(string $id): Wrapper
    {
        return $this->wrapper = new Wrapper($id, new MockedRecord(), new ReplaceEntry($id, $this));
    }

    public function setRecord(string $id, Records\Record $record): void
    {
        $this->setRecords[] = [$id, $record];
    }

    public function setContainer(string $id, ContainerInterface $container): void
    {
        $this->setContainers[] = [$id, $container];
    }

    protected function records(): Records
    {
        return new Records($this->records);
    }
}
