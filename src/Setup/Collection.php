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
use Polymorphine\Container\Exception;


class Collection
{
    private $records;

    /**
     * @param Records\Record[] $records
     */
    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public function records(bool $tracking = false): Records
    {
        return $tracking ? new Records\TrackedRecords($this->records) : new Records\RecordCollection($this->records);
    }

    public function add(string $id, Records\Record $record): void
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
}
