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

    private $addedRecords       = [];
    private $replacedRecords    = [];
    private $addedContainers    = [];
    private $replacedContainers = [];

    private $replace;

    public static function added(): self
    {
        $object = new self();
        $object->replace = false;
        return $object;
    }

    public static function replaced(): self
    {
        $object = new self();
        $object->replace = true;
        return $object;
    }

    public function container(): ContainerInterface
    {
        return $this->container = new FakeContainer();
    }

    public function decorator(string $id): Wrapper
    {
        return $this->wrapper = new Wrapper($id, new MockedRecord(), new ReplaceEntry($id, $this));
    }

    public function recordChanges(): array
    {
        return $this->replace ? $this->replacedRecords : $this->addedRecords;
    }

    public function containerChanges(): array
    {
        return $this->replace ? $this->replacedContainers : $this->addedContainers;
    }

    public function addRecord(string $id, Records\Record $record): void
    {
        $this->addedRecords[] = [$id, $record];
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->addedContainers[] = [$id, $container];
    }

    public function replaceRecord(string $id, Records\Record $record): void
    {
        $this->replacedRecords[] = [$id, $record];
    }

    public function replaceContainer(string $id, ContainerInterface $container): void
    {
        $this->replacedContainers[] = [$id, $container];
    }

    protected function records(): Records
    {
        return new Records($this->records);
    }
}
