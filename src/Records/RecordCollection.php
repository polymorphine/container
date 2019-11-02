<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Records;

use Polymorphine\Container\Records;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class RecordCollection implements Records
{
    private $records;

    /**
     * @param Record[] $records Associative (flat) array of Record entries
     */
    public function __construct(array $records = [])
    {
        $this->records = $records;
    }

    public function has(string $id): bool
    {
        return isset($this->records[$id]);
    }

    public function get(string $id, ContainerInterface $container)
    {
        return $this->getRecord($id)->value($container);
    }

    public function add(string $id, Record $record): void
    {
        if (isset($this->records[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $id));
        }

        $this->records[$id] = $record;
    }

    public function moveRecord(string $id): string
    {
        if (!isset($this->records[$id])) {
            $message = 'Undefined `%s` record cannot be moved';
            throw new Exception\RecordNotFoundException(sprintf($message, $id));
        }

        $newId = $id . '.WRAP';
        while (isset($this->records[$newId])) {
            $newId .= '.WRAP';
        }

        $this->records[$newId] = $this->records[$id];
        unset($this->records[$id]);

        return $newId;
    }

    private function getRecord(string $id): Record
    {
        if (!isset($this->records[$id])) {
            throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $id));
        }
        return $this->records[$id];
    }
}
