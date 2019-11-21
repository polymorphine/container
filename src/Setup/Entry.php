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

use Polymorphine\Container\Setup;
use Polymorphine\Container\Records\Record;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


/**
 * Write-only proxy with helper methods to instantiate and set
 * Record implementations for given Container item identifier.
 */
class Entry
{
    private $id;
    private $builder;

    public function __construct(string $id, Setup $builder)
    {
        $this->id      = $id;
        $this->builder = $builder;
    }

    /**
     * Adds given Record instance directly into container records
     * using this instance's id property.
     *
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function record(Record $record): void
    {
        $this->builder->addRecord($this->id, $record);
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
     * Adds InstanceRecord to container records with given className
     * and its constructor parameters given as Container identifiers.
     *
     * @see InstanceRecord
     *
     * @param string $className
     * @param string ...$dependencies
     *
     * @throws Exception\InvalidIdException
     */
    public function instance(string $className, string ...$dependencies): void
    {
        $this->record(new Record\InstanceRecord($className, ...$dependencies));
    }

    /**
     * Adds ProductRecord to container records with given container
     * identifier of factory class, factory method name and container
     * identifiers of its parameters.
     *
     * @see ProductRecord
     *
     * @param string $factoryId
     * @param string $method
     * @param string ...$arguments
     *
     * @throws Exception\InvalidIdException
     */
    public function product(string $factoryId, string $method, string ...$arguments): void
    {
        $this->record(new Record\ProductRecord($factoryId, $method, ...$arguments));
    }

    /**
     * Adds ContainerInterface instance as sub-container that may
     * be accessed with this instance id as prefix.
     *
     * WARNING: For id containing prefix separator exception will
     * be thrown.
     *
     * @param ContainerInterface $container
     *
     * @throws Exception\InvalidIdException
     */
    public function container(ContainerInterface $container)
    {
        $this->builder->addContainer($this->id, $container);
    }
}
