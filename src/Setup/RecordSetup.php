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

use Polymorphine\Container\Exception;
use Closure;


/**
 * Write-only proxy with helper methods to instantiate and
 * set Record implementations for given Container name id.
 */
class RecordSetup
{
    private $name;
    private $records;

    public function __construct(string $name, RecordCollection $records)
    {
        $this->name    = $name;
        $this->records = $records;
    }

    /**
     * Pushes given Record instance directly into Container's records.
     *
     * @see Record
     *
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function record(Record $record): void
    {
        $this->records->set($this->name, $record);
    }

    /**
     * Pushes DirectRecord with given value into Container's records.
     *
     * @see DirectRecord
     *
     * @param $value
     */
    public function value($value): void
    {
        $this->record(new Record\DirectRecord($value));
    }

    /**
     * Pushes LazyRecord with given Closure into Container's records.
     * Closure receives Container instance as parameter.
     *
     * @see LazyRecord
     *
     * @param Closure $closure
     */
    public function lazy(Closure $closure): void
    {
        $this->record(new Record\LazyRecord($closure));
    }

    /**
     * Pushes FactoryRecord with given className and its constructor
     * parameters given as Container id names. Each dependency has
     * to be defined within collection (otherwise circular references
     * cannot be avoided).
     *
     * When dependency id equals this instance name it is not overwritten and
     * circular dependency is not created - it is decorated instead.
     * Now every class depending on decorated object will take product of this
     * record as its dependency. Objects can be decorated multiple times.
     *
     * @see FactoryRecord
     *
     * @param string   $className
     * @param string[] ...$dependencies
     */
    public function factory(string $className, string ...$dependencies): void
    {
        $dependencies = $this->validDependencies($dependencies);
        $this->record(new Record\FactoryRecord($className, ...$dependencies));
    }

    private function validDependencies(array $dependencies): array
    {
        foreach ($dependencies as &$name) {
            $this->checkIfDefined($name);
            if ($name === $this->name) {
                $name = $this->decoratedDependency();
            }
        }

        return $dependencies;
    }

    private function checkIfDefined(string $name): void
    {
        if (!$this->records->has($name)) {
            throw new Exception\RecordNotFoundException(
                sprintf('FactoryRecord requires defined dependencies - `%s` Record not found', $name)
            );
        }
    }

    private function decoratedDependency(): string
    {
        $newAlias = $this->name . '.DEC';
        while ($this->records->has($newAlias)) {
            $newAlias .= '.DEC';
        }

        $this->records->set($newAlias, $this->records->get($this->name));
        $this->records->remove($this->name);

        return $newAlias;
    }
}
