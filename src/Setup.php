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

use Psr\Container\ContainerInterface;


class Setup
{
    private $collection;

    public function __construct(Setup\Collection $collection = null)
    {
        $this->collection = $collection ?: new Setup\Collection();
    }

    /**
     * Creates Setup with validated collection.
     *
     * Added entries will be validated for identifier conflicts and
     * created container will be monitored for circular references.
     *
     * @return self
     */
    public static function secure(): self
    {
        return new self(new Setup\ValidatedCollection());
    }

    /**
     * Creates Setup with predefined configuration.
     *
     * If `true` is passed as $validate param secure version of Setup
     * will be created and predefined configuration will be validated.
     *
     * @see Setup::secure()
     *
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     * @param bool                 $validate
     *
     * @return self
     */
    public static function withData(array $records = [], array $containers = [], bool $validate = false): self
    {
        $collection = $validate
            ? new Setup\ValidatedCollection($records, $containers)
            : new Setup\Collection($records, $containers);
        return new self($collection);
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
        return $this->collection->container();
    }

    /**
     * Returns Entry object able to add new data to container configuration
     * for given identifier.
     *
     * @param string $name
     *
     * @return Setup\Entry
     */
    public function entry(string $name): Setup\Entry
    {
        return new Setup\Entry($name, $this->collection);
    }

    /**
     * Adds Record instances directly to container configuration.
     *
     * @param Records\Record[] $records Flat associative array of Record instances
     *
     * @throws Exception\InvalidIdException
     */
    public function records(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->collection->addRecord($id, $record);
        }
    }
}
