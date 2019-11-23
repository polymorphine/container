<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Entry;

use Polymorphine\Container\Setup\Entry;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Setup\Exception;


class Wrapper
{
    private $id;
    private $record;
    private $entry;
    private $wrappers = [];

    public function __construct(string $wrappedId, Record $wrappedRecord, Entry $entry)
    {
        $this->id     = $wrappedId;
        $this->record = $wrappedRecord;
        $this->entry  = $entry;
    }

    public function with(string $className, string ...$dependencies): self
    {
        $idx = array_search($this->id, $dependencies, true);
        if ($idx === false) {
            throw Exception\IntegrityConstraintException::missingReference($this->id);
        }

        $this->wrappers[] = [$className, $dependencies];
        return $this;
    }

    public function compose(): void
    {
        $this->entry->record(new Record\ComposedInstanceRecord($this->id, $this->record, $this->wrappers));
    }
}
