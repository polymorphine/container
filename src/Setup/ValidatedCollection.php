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

use Polymorphine\Container\Records;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;
use Psr\Container\ContainerInterface;


class ValidatedCollection extends Collection
{
    public function container(): ContainerInterface
    {
        return $this->containers
            ? new CompositeContainer(new Records\TrackedRecords($this->records), $this->containers)
            : new RecordContainer(new Records\TrackedRecords($this->records));
    }
}
