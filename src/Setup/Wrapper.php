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

use Polymorphine\Container\Records\Record;


class Wrapper
{
    private $wrappedId;
    private $wrappedRecord;
    private $storeCallback;
    private $wrappers = [];

    public function __construct(string $wrappedId, Record $wrappedRecord, callable $storeCallback)
    {
        $this->wrappedId     = $wrappedId;
        $this->wrappedRecord = $wrappedRecord;
        $this->storeCallback = $storeCallback;
    }

    public function with(string $className, string ...$dependencies): self
    {
        $idx = array_search($this->wrappedId, $dependencies, true);
        if ($idx === false) {
            throw Exception\IntegrityConstraintException::undefined($this->wrappedId);
        }

        $this->wrappers[] = [$className, $dependencies];
        return $this;
    }

    public function compose(): void
    {
        $record = new Record\ComposedInstanceRecord($this->wrappedId, $this->wrappedRecord, $this->wrappers);
        ($this->storeCallback)($record);
    }
}
