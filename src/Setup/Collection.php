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
     * @param string         $id
     * @param Records\Record $record
     *
     * @throws Exception\IntegrityConstraintException
     */
    public function addRecord(string $id, Records\Record $record): void;

    /**
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Exception\IntegrityConstraintException
     */
    public function addContainer(string $id, ContainerInterface $container): void;

    /**
     * @param string         $id
     * @param Records\Record $record
     *
     * @throws Exception\IntegrityConstraintException
     */
    public function replaceRecord(string $id, Records\Record $record): void;

    /**
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Exception\IntegrityConstraintException
     */
    public function replaceContainer(string $id, ContainerInterface $container): void;
}
