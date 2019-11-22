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

use Polymorphine\Container\Setup\Exception;
use Polymorphine\Container\Setup\Wrapper;
use Psr\Container\ContainerInterface;


class Setup
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
    public function entry(string $id): Setup\Entry
    {
        return new Setup\Entry($id, $this);
    }

    public function wrap(string $id): Setup\Wrapper
    {
        if (!isset($this->records[$id])) {
            throw Exception\IntegrityConstraintException::undefined($id);
        }

        $replace = function (Records\Record $record) use ($id): void {
            $this->records[$id] = $record;
        };

        return new Wrapper($id, $this->records[$id], $replace);
    }

    /**
     * Adds Record instances directly to container configuration.
     *
     * @param Records\Record[] $records Flat associative array of Record instances
     *
     * @throws Exception\IntegrityConstraintException
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
     * @throws Exception\IntegrityConstraintException
     */
    public function addRecord(string $id, Records\Record $record): void
    {
        $this->records[$id] = $record;
    }

    /**
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Exception\IntegrityConstraintException
     */
    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->containers[$id] = $container;
    }

    protected function records(): Records
    {
        return new Records($this->records);
    }
}
