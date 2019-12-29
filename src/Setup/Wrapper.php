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


/**
 * ComposedInstanceRecord definition builder.
 */
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

    /**
     * Defines class instantiation wrapping previously defined instance.
     * This method is similar to Entry::instance() except it returns its
     * own instance until Wrapper::compose() is called, and valid definition
     * requires current entry identifier as one of given dependencies to
     * inject wrapped object. Otherwise exception will be thrown.
     *
     * @see Entry::instance()
     * @see Wrapper::compose()
     *
     * @param string $className
     * @param string ...$dependencies
     *
     * @throws Exception\IntegrityConstraintException
     *
     * @return $this
     */
    public function with(string $className, string ...$dependencies): self
    {
        $idx = array_search($this->id, $dependencies, true);
        if ($idx === false) {
            throw Exception\IntegrityConstraintException::missingReference($this->id);
        }

        $this->wrappers[] = [$className, $dependencies];
        return $this;
    }

    /**
     * Adds ComposedInstanceRecord created with collected wrappers composition
     * to container records.
     */
    public function compose(): void
    {
        $this->entry->record(new Record\ComposedInstanceRecord($this->id, $this->record, $this->wrappers));
    }
}
