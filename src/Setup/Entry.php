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
use Psr\Container\ContainerInterface;


/**
 * Write-only proxy with helper methods to instantiate and set
 * Record implementations for given Container item identifier.
 */
abstract class Entry
{
    protected $id;
    protected $builder;

    public function __construct(string $id, Collection $build)
    {
        $this->id      = $id;
        $this->builder = $build;
    }

    /**
     * Sets given Record instance directly into container records
     * using this instance's id property.
     *
     * @param Record $record
     *
     * @throws Exception\IntegrityConstraintException
     */
    abstract public function record(Record $record): void;

    /**
     * Sets ContainerInterface instance as sub-container that may
     * be accessed with this instance id as prefix.
     *
     * WARNING: For id containing prefix separator exception will
     * be thrown.
     *
     * @param ContainerInterface $container
     *
     * @throws Exception\IntegrityConstraintException
     */
    abstract public function container(ContainerInterface $container): void;

    /**
     * Adds ValueRecord with given value into container records.
     *
     * @see ValueRecord
     *
     * @param $value
     *
     * @throws Exception\IntegrityConstraintException
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
     * @throws Exception\IntegrityConstraintException
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
     * @throws Exception\IntegrityConstraintException
     */
    public function instance(string $className, string ...$dependencies): void
    {
        $this->record(new Record\InstanceRecord($className, ...$dependencies));
    }

    /**
     * Creates InstanceRecord and returns Wrapper object that allows
     * to define subsequent instanceRecords using this Entry id as
     * one of its dependencies (reference to itself). This will create
     * single Record being composition of all defined instances.
     *
     * If wrapping record doesn't use reference to itself as one of
     * dependencies IntegrityConstraintException will be thrown.
     *
     * Composition is finished with Wrapper::compose() call that will
     * add ComposedInstanceRecord to container records.
     *
     * @see Record\InstanceRecord
     * @see Entry\Wrapper
     *
     * @param string $className
     * @param string ...$dependencies
     *
     * @return Entry\Wrapper
     */
    public function wrappedInstance(string $className, string ...$dependencies): Entry\Wrapper
    {
        return new Entry\Wrapper($this->id, new Record\InstanceRecord($className, ...$dependencies), $this);
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
     * @throws Exception\IntegrityConstraintException
     */
    public function product(string $factoryId, string $method, string ...$arguments): void
    {
        $this->record(new Record\ProductRecord($factoryId, $method, ...$arguments));
    }
}
