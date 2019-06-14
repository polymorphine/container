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
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function useRecord(Record $record): void
    {
        $this->records->add($this->name, $record);
    }

    /**
     * Pushes ValueRecord with given value into Container's records.
     *
     * @see ValueRecord
     *
     * @param $value
     *
     * @throws Exception\InvalidIdException
     */
    public function set($value): void
    {
        $this->useRecord(new Record\ValueRecord($value));
    }

    /**
     * Pushes CallbackRecord with given callable into Container's records.
     * Callback receives ContainerInterface instance as parameter.
     *
     * @see CallbackRecord
     *
     * @param callable $callback
     *
     * @throws Exception\InvalidIdException
     */
    public function invoke(callable $callback): void
    {
        $this->useRecord(new Record\CallbackRecord($callback));
    }

    /**
     * Pushes CompositeRecord with given className and its constructor
     * parameters given as Container id names. Each dependency has
     * to be defined within collection (otherwise circular references
     * cannot be avoided).
     *
     * When dependency id equals this instance name it is not overwritten and
     * circular dependency is not created - it is decorated instead.
     * Now every class depending on decorated object will take product of this
     * record as its dependency. Objects can be decorated multiple times.
     *
     * @see CompositeRecord
     *
     * @param string $className
     * @param string ...$dependencies
     *
     * @throws Exception\InvalidIdException | Exception\RecordNotFoundException
     */
    public function compose(string $className, string ...$dependencies): void
    {
        $idx = array_search($this->name, $dependencies, true);
        if ($idx !== false && !$this->records->isConfigId($this->name)) {
            $dependencies[$idx] = $this->decoratedRecordAlias();
        }

        $this->useRecord(new Record\CompositeRecord($className, ...$dependencies));
    }

    /**
     * Pushes CreateMethodRecord with given method of container identified
     * object and its parameter values.
     *
     * @see CreateMethodRecord
     *
     * @param string $method       format: `method@containerId`
     * @param mixed  ...$arguments
     *
     * @throws Exception\InvalidIdException | Exception\RecordNotFoundException
     */
    public function call(string $method, ...$arguments): void
    {
        $this->useRecord(new Record\CreateMethodRecord($method, $arguments));
    }

    private function decoratedRecordAlias(): string
    {
        if (!$this->records->has($this->name)) {
            $message = 'Undefined `%s` record for decorating composition';
            throw new Exception\RecordNotFoundException(sprintf($message, $this->name));
        }

        $newAlias = $this->name . '.DEC';
        while ($this->records->has($newAlias)) {
            $newAlias .= '.DEC';
        }

        $this->records->add($newAlias, $this->records->get($this->name));
        $this->records->remove($this->name);

        return $newAlias;
    }
}
