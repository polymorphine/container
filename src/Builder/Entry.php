<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Builder;

use Polymorphine\Container\Builder;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


/**
 * Write-only proxy with helper methods to instantiate and set
 * Record implementations for given Container name identifier.
 */
class Entry
{
    private $name;
    private $builder;

    public function __construct(string $name, Builder $builder)
    {
        $this->name    = $name;
        $this->builder = $builder;
    }

    /**
     * Adds given Record instance directly into container records
     * using this instance's name property.
     *
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function record(Record $record): void
    {
        $this->builder->addRecord($this->name, $record);
    }

    /**
     * Adds ValueRecord with given value into container records.
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
     * Adds CallbackRecord with given callable into container records.
     * Callback receives ContainerInterface instance as parameter.
     *
     * @see CallbackRecord
     *
     * @param callable $callback function (ContainerInterface): mixed
     *
     * @throws Exception\InvalidIdException
     */
    public function callback(callable $callback): void
    {
        $this->record(new Record\CallbackRecord($callback));
    }

    /**
     * Adds ComposeRecord to container records with given className
     * and its constructor parameters given as Container id names.
     * Each dependency has to be defined within collection (otherwise
     * circular references cannot be avoided).
     *
     * When dependency id equals this instance name it is not overwritten
     * and circular dependency is not created - it is decorated instead.
     * Now every class depending on decorated object will take product of
     * this record as its dependency. Objects can be wrapped multiple times.
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
            $dependencies[$idx] = $this->builder->wrapRecord($this->name);
        }

        $this->record(new Record\ComposeRecord($className, ...$dependencies));
    }

    /**
     * Adds CreateMethodRecord to container records with given container
     * identifier of factory class, factory method name and container
     * identifiers of its parameters.
     *
     * @see CreateMethodRecord
     *
     * @param string $factoryId
     * @param string $method
     * @param string ...$arguments
     *
     * @throws Exception\InvalidIdException
     */
    public function create(string $factoryId, string $method, string ...$arguments): void
    {
        $this->record(new Record\CreateMethodRecord($factoryId, $method, ...$arguments));
    }

    /**
     * Adds ContainerInterface instance as sub-container that may
     * be accessed with current entry name prefix (entry name
     * cannot contain prefix separator).
     *
     * @param ContainerInterface $container
     *
     * @throws Exception\InvalidIdException
     */
    public function container(ContainerInterface $container)
    {
        $this->builder->addContainer($this->name, $container);
    }
}
