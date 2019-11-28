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


/**
 * Instance of Records with nested call tracking, detecting
 * circular calls to passed ContainerInterface and appending
 * call stack paths to exception messages.
 */
class TrackedRecords extends Records
{
    private $callStack = [];

    public function get(string $id, ContainerInterface $container)
    {
        if (isset($this->callStack[$id])) {
            throw new Exception\CircularReferenceException($id, $this->callStack);
        }

        $this->callStack[$id] = true;

        try {
            $item = parent::get($id, $container);
        } catch (Exception\RecordNotFoundException $e) {
            $message     = $e->getMessage();
            $unstackedId = $this->unstackedId($message, $id);
            throw new Exception\TrackedRecordNotFoundException($message, $this->callStack, $unstackedId);
        }

        unset($this->callStack[$id]);
        return $item;
    }

    private function unstackedId(string $message, string $id): ?string
    {
        $idFound = preg_match('#`(?P<id>.+?)`#', $message, $matches);
        if (!$idFound || $matches['id'] === $id) { return null; }
        return $matches['id'];
    }
}
