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
use Psr\Container\ContainerInterface;


interface Collection
{
    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * @param string         $id
     * @param Records\Record $record
     */
    public function setRecord(string $id, Records\Record $record): void;

    /**
     * @param string             $id
     * @param ContainerInterface $container
     */
    public function setContainer(string $id, ContainerInterface $container): void;
}
