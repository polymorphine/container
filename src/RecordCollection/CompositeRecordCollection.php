<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\RecordCollection;

use Polymorphine\Container\RecordCollection;
use Polymorphine\Container\Record;


class CompositeRecordCollection implements RecordCollection
{
    private $primary;
    private $secondary;

    public function __construct(RecordCollection $primary, RecordCollection $secondary)
    {
        $this->primary   = $primary;
        $this->secondary = $secondary;
    }

    public function get(string $id): Record
    {
        return $this->primary->has($id) ? $this->primary->get($id) : $this->secondary->get($id);
    }

    public function has(string $id): bool
    {
        return $this->primary->has($id) || $this->secondary->has($id);
    }
}
