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


class Container implements ContainerInterface
{
    private $records;

    public function __construct(RecordCollection $records)
    {
        $this->records = $records;
    }

    public static function fromRecordsArray(array $records): self
    {
        return new self(new RecordCollection($records));
    }

    public function get($id)
    {
        return $this->records->get($id)->value($this);
    }

    public function has($id): bool
    {
        return $this->records->has($id);
    }
}
