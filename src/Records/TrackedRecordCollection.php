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

use Polymorphine\Container\Exception;
use Polymorphine\Container\Records;
use Psr\Container\ContainerInterface;


class TrackedRecordCollection implements Records
{
    private $records;
    private $callStack = [];

    public function __construct(Records $records)
    {
        $this->records = $records;
    }

    public function has(string $id): bool
    {
        return $this->records->has($id);
    }

    public function get(string $id, ContainerInterface $container)
    {
        if (isset($this->callStack[$id])) {
            $message = 'Lazy composition of `%s` record is using reference to itself [call stack: %s ]';
            throw new Exception\CircularReferenceException(sprintf($message, (string) $id, $this->callStackPath($id)));
        }

        try {
            $this->callStack[$id] = true;
            $item = $this->records->get($id, $container);
        } catch (Exception\RecordNotFoundException $e) {
            throw $e->withCallStack($this->callStackPath($id));
        }

        unset($this->callStack[$id]);
        return $item;
    }

    public function add(string $id, Record $record): void
    {
        $this->records->add($id, $record);
    }

    public function moveRecord(string $id): string
    {
        return $this->records->moveRecord($id);
    }

    private function callStackPath(string $id): string
    {
        return implode('->', array_keys($this->callStack)) . '->' . $id;
    }
}
