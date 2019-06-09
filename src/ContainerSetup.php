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

use Polymorphine\Container\Exception\InvalidArgumentException;
use Polymorphine\Container\RecordCollection\MainRecordCollection;
use Polymorphine\Container\RecordCollection\CompositeRecordCollection;
use Polymorphine\Container\RecordCollection\ConfigRecordsTree;
use Psr\Container\ContainerInterface;


class ContainerSetup
{
    private $records;
    private $configs = [];
    private $tracking;
    private $container;

    /**
     * @param Record[] $records
     * @param bool     $tracking Check for circular references (dev-mode)
     *
     * @throws Exception\InvalidArgumentException | Exception\InvalidIdException
     */
    public function __construct(array $records = [], bool $tracking = false)
    {
        $this->records  = new MainRecordCollection($records);
        $this->tracking = $tracking;
    }

    /**
     * Returns Container instance with provided records.
     *
     * Adding new entries to container is possible only through
     * this setup instance, but early access to container might be
     * necessary. Cannot lock instance right after exposing it - for
     * example routing is both stored within container and built
     * with references to its records.
     *
     * Strict immutability cannot be ensured, because side-effects
     * can change subsequent call outcomes for stored identifiers.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        if ($this->container) { return $this->container; }

        $this->container = $this->tracking
            ? new TrackingContainer($this->recordsCollection())
            : new Container($this->recordsCollection());

        return $this->container;
    }

    /**
     * Returns RecordSetup object able to configure Container's
     * Record slot under given name id.
     *
     * @param string $name
     *
     * @return RecordSetup
     */
    public function entry(string $name): RecordSetup
    {
        return new RecordSetup($name, $this->records);
    }

    public function config(string $name, array $config): void
    {
        $this->records->preventOverwrite($name);

        if (strpos($name, ConfigRecordsTree::SEPARATOR) !== false) {
            $message = 'Path separator `%s` used in `%s` config name';
            throw new InvalidArgumentException(sprintf($message, ConfigRecordsTree::SEPARATOR, $name));
        }

        if (isset($this->configs[$name])) {
            $message = 'Config `%s` already loaded';
            throw new InvalidArgumentException(sprintf($message, $name));
        }

        $this->configs[$name] = $config;
    }

    public function exists(string $name): bool
    {
        return $this->records->has($name);
    }

    private function recordsCollection(): RecordCollection
    {
        return $this->configs
            ? new CompositeRecordCollection($this->records, new ConfigRecordsTree($this->configs))
            : $this->records;
    }
}
