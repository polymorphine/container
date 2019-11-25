<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup;

use Polymorphine\Container\Setup;
use Polymorphine\Container\Records;
use Psr\Container\ContainerInterface;


class BasicSetup extends Setup
{
    public function addRecord(string $id, Records\Record $record): void
    {
        $this->records[$id] = $record;
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->containers[$id] = $container;
    }

    public function replaceRecord(string $id, Records\Record $record): void
    {
        $this->records[$id] = $record;
    }

    public function replaceContainer(string $id, ContainerInterface $container): void
    {
        $this->containers[$id] = $container;
    }

    protected function records(): Records
    {
        return new Records($this->records);
    }
}
