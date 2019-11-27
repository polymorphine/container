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

use Polymorphine\Container\Setup;
use Polymorphine\Container\Records;
use Psr\Container\ContainerInterface;


class MockedSetup extends Setup
{
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
        return new Records();
    }
}
