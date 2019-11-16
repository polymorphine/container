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
    private $builder;

    public function __construct(Builder $builder = null)
    {
        $this->builder = $builder ?: new Builder();
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
        return new self(new Builder\ValidatedBuilder());
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
            ? new Builder\ValidatedBuilder($records, $containers)
            : new Builder($records, $containers);
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
        return $this->builder->container();
    }

    /**
     * Returns Entry object able to add new data to container configuration
     * for given identifier.
     *
     * @param string $name
     *
     * @return Builder\Entry
     */
    public function entry(string $name): Builder\Entry
    {
        return new Builder\Entry($name, $this->builder);
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
            $this->builder->addRecord($id, $record);
        }
    }
}
