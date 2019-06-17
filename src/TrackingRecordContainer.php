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


/**
 * Container that keeps track of called record sequences to display verbose
 * Exception messages and prevent circular references causing callback loop
 * stack overflow.
 *
 * This mechanism adds substantial execution overhead and should not
 * be used in production.
 */
class TrackingRecordContainer extends RecordContainer
{
    private $references = [];

    public function get($id)
    {
        if (isset($this->references[$id])) {
            $message = 'Lazy composition of `%s` record is using reference to itself [call stack: %s ]';
            throw new Exception\CircularReferenceException(sprintf($message, (string) $id, $this->callStackPath($id)));
        }

        try {
            $track = clone $this;
            $track->references[$id] = true;
            return $this->records->get($id, $track);
        } catch (Exception\RecordNotFoundException $e) {
            throw $e->withCallStack($this->callStackPath($id));
        }
    }

    private function callStackPath(string $id): string
    {
        return implode('->', array_keys($this->references)) . '->' . $id;
    }
}
