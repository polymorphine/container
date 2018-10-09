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

use Psr\Container\ContainerInterface;


class TrackingContainer implements ContainerInterface
{
    private $records;
    private $references = [];

    public function __construct(RecordCollection $records)
    {
        $this->records = $records;
    }

    public function get($id)
    {
        if (isset($this->references[$id])) {
            $message = 'Lazy composition of `%s` record is using reference to itself';
            throw new Exception\CircularReferenceException(sprintf($message, (string) $id));
        }

        $track = clone $this;
        $track->references[$id] = true;
        return $this->records->get($id)->value($track);
    }

    public function has($id)
    {
        return $this->records->has($id);
    }
}
