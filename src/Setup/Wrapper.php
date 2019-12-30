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

    public function __construct(string $id, Record $record, Entry $entry)
    {
        $this->id     = $id;
        $this->record = $record;
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
        $dependencies = $this->clearWrapped($dependencies);
        $this->record = new Record\ComposedInstanceRecord($className, $this->record, ...$dependencies);
        return $this;
    }

    /**
     * Adds ComposedInstanceRecord created with collected wrappers composition
     * to container records.
     */
    public function compose(): void
    {
        $this->entry->record($this->record);
    }

    private function clearWrapped(array $dependencies): array
    {
        $valid = false;
        foreach ($dependencies as &$id) {
            if ($id !== $this->id) { continue; }
            $id    = null;
            $valid = true;
        }

        if (!$valid) {
            throw Exception\IntegrityConstraintException::missingReference($this->id);
        }

        return $dependencies;
    }
}
