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
use Psr\Container\ContainerInterface;


class TrackedRecords extends RecordCollection
{
    private $callStack = [];

    public function get(string $id, ContainerInterface $container)
    {
        if (isset($this->callStack[$id])) {
            $message = 'Lazy composition of `%s` record is using reference to itself [call stack: %s]';
            throw new Exception\CircularReferenceException(sprintf($message, (string) $id, $this->callStackPath($id)));
        }

        $this->callStack[$id] = true;

        try {
            $item = parent::get($id, $container);
        } catch (Exception\RecordNotFoundException $e) {
            throw $e->withCallStack($this->callStackPath('...'));
        }

        unset($this->callStack[$id]);
        return $item;
    }

    private function callStackPath(string $id): string
    {
        return implode('->', array_keys($this->callStack)) . '->' . $id;
    }
}
