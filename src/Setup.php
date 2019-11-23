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

use Polymorphine\Container\Setup\Entry;
use Psr\Container\ContainerInterface;


abstract class Setup
{
    protected $records;
    protected $containers;

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     */
    public function __construct(array $records = [], array $containers = [])
    {
        $this->records    = $records;
        $this->containers = $containers;
    }

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     *
     * @return static
     */
    public static function basic(array $records = [], array $containers = []): self
    {
        return new Setup\BasicSetup($records, $containers);
    }

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     *
     * @return static
     */
    public static function validated(array $records = [], array $containers = []): self
    {
        return new Setup\ValidatedSetup($records, $containers);
    }

    /**
     * Returns immutable Container instance with provided data.
     *
     * Adding new entries to this setup is still possible, but created
     * container will not be affected and this method will create new
     * container instance with those added entries.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->containers
            ? new CompositeContainer($this->records(), $this->containers)
            : new RecordContainer($this->records());
    }

    /**
     * Returns Entry object able to add new data to container configuration
     * for given identifier.
     *
     * @param string $id
     *
     * @return Setup\Entry
     */
    public function add(string $id): Entry
    {
        return new Entry\AddEntry($id, $this);
    }

    /**
     * Returns Entry object able to replace data in container configuration
     * for given identifier.
     *
     * @param string $id
     *
     * @return Setup\Entry
     */
    public function replace(string $id): Entry
    {
        return new Entry\ReplaceEntry($id, $this);
    }

    /**
     * Returns Wrapper object able to decorate existing Record and replacing
     * it with composition of InstanceRecords using given id as a reference
     * to one of their dependencies (reference to itself).
     *
     * If given id is not defined or wrapping record doesn't use its reference
     * as one of dependencies IntegrityConstraintException will be thrown.
     *
     * Composition is finished with Wrapper::compose() call that will
     * replace initial entry with ComposedInstanceRecord.
     *
     * @see \Polymorphine\Container\Records\Record\InstanceRecord
     *
     * @param string $id
     *
     * @throws Setup\Exception\IntegrityConstraintException
     *
     * @return Setup\Entry\Wrapper
     */
    public function decorate(string $id): Entry\Wrapper
    {
        if (!isset($this->records[$id])) {
            throw Setup\Exception\IntegrityConstraintException::undefined($id);
        }

        return new Entry\Wrapper($id, $this->records[$id], new Entry\ReplaceEntry($id, $this));
    }

    /**
     * Adds Record instances directly to container configuration.
     *
     * @param Records\Record[] $records Flat associative array of Record instances
     *
     * @throws Setup\Exception\IntegrityConstraintException
     */
    public function addRecords(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->addRecord($id, $record);
        }
    }

    /**
     * @param string         $id
     * @param Records\Record $record
     *
     * @throws Setup\Exception\IntegrityConstraintException
     */
    abstract public function addRecord(string $id, Records\Record $record): void;

    /**
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Setup\Exception\IntegrityConstraintException
     */
    abstract public function addContainer(string $id, ContainerInterface $container): void;

    /**
     * @param string         $id
     * @param Records\Record $record
     *
     * @throws Setup\Exception\IntegrityConstraintException
     */
    abstract public function replaceRecord(string $id, Records\Record $record): void;

    /**
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Setup\Exception\IntegrityConstraintException
     */
    abstract public function replaceContainer(string $id, ContainerInterface $container): void;

    abstract protected function records(): Records;
}
