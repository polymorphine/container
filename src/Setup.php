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

    public static function secure(): self
    {
        return new self(new Setup\ValidatedCollection());
    }

    /**
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
     * Returns Container instance with provided records.
     *
     * Adding new entries to container is still possible, but only
     * using this instance's entry() method.
     *
     * Strict immutability can be ensured only when this instance is
     * encapsulated and not passed to uncontrolled parts of application
     * (including container itself).
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->collection->container();
    }

    /**
     * Returns Entry object able to configure Container's
     * data slot for given name id.
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
     * Stores Records instantiated directly in container.
     *
     * @param Records\Record[] $records Flat associative array of Record instances
     *
     * @throws Exception\InvalidIdException
     */
    public function records(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->collection->add($id, $record);
        }
    }
}
