<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Entry;

use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Setup\Entry;
use Psr\Container\ContainerInterface;


class AddEntry extends Entry
{
    public function record(Record $record): void
    {
        $this->builder->addRecord($this->id, $record);
    }

    public function container(ContainerInterface $container): void
    {
        $this->builder->addContainer($this->id, $container);
    }
}
