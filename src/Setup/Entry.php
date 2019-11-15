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
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


/**
 * Write-only proxy with helper methods to instantiate and
 * set Record implementations for given Container name id.
 */
class Entry
{
    private $name;
    private $records;

    public function __construct(string $name, Collection $records)
    {
        $this->name    = $name;
        $this->records = $records;
    }

    /**
     * Pushes given Record instance directly into Container's records
     * using this instance's name property.
     *
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function record(Record $record): void
    {
        $this->records->addRecord($this->name, $record);
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
    public function value($value): void
    {
        $this->record(new Record\ValueRecord($value));
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
    public function callback(callable $callback): void
    {
        $this->record(new Record\CallbackRecord($callback));
    }

    /**
     * Pushes ComposeRecord with given className and its constructor
     * parameters given as Container id names. Each dependency has
     * to be defined within collection (otherwise circular references
     * cannot be avoided).
     *
     * When dependency id equals this instance name it is not overwritten and
     * circular dependency is not created - it is decorated instead.
     * Now every class depending on decorated object will take product of this
     * record as its dependency. Objects can be decorated multiple times.
     *
     * @see ComposeRecord
     *
     * @param string $className
     * @param string ...$dependencies
     *
     * @throws Exception\InvalidIdException | Exception\RecordNotFoundException
     */
    public function compose(string $className, string ...$dependencies): void
    {
        $idx = array_search($this->name, $dependencies, true);
        if ($idx !== false) {
            $dependencies[$idx] = $this->records->wrapRecord($this->name);
        }

        $this->record(new Record\ComposeRecord($className, ...$dependencies));
    }

    /**
     * Pushes CreateMethodRecord with given method of container identified
     * factory and its parameter values as container identifiers.
     *
     * @see CreateMethodRecord
     *
     * @param string $factoryId
     * @param string $method
     * @param string ...$arguments Container identifiers of stored arguments
     *
     * @throws Exception\InvalidIdException | Exception\RecordNotFoundException
     */
    public function create(string $factoryId, string $method, string ...$arguments): void
    {
        $this->record(new Record\CreateMethodRecord($factoryId, $method, ...$arguments));
    }

    public function container(ContainerInterface $container)
    {
        $this->records->addContainer($this->name, $container);
    }
}
