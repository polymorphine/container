<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container;


interface RecordCollection
{
    /**
     * Checks if Record is stored under given name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isDefined(string $name): bool;

    /**
     * Returns Record stored under given name and removes it from collection.
     *
     * @param string $name
     *
     * @throws Exception\RecordNotFoundException
     *
     * @return Record
     */
    public function pull(string $name): Record;

    /**
     * Stores Record under given $name identifier.
     * Behavior of Container returning given Record's value
     * depends on passed Record's implementation.
     *
     * @param $name
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function push(string $name, Record $record): void;
}
