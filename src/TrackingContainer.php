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
use Polymorphine\Container\Setup\RecordCollection;
use Polymorphine\Container\Exception\CircularReferenceException;


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
            throw new CircularReferenceException(sprintf($message, (string) $id));
        }

        $this->references[$id] = true;
        return $this->records->get($id)->value($this);
    }

    public function has($id)
    {
        return $this->records->has($id);
    }
}
