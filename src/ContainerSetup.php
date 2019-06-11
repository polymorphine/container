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


class ContainerSetup
{
    private $records;
    private $container;

    /**
     * Config can be multidimensional array which values would be
     * accessed using path notation, therefore array keys cannot
     * contain path separator (default: '.').
     *
     * @param array    $config  Associative (multidimensional) array of configuration values
     */
    public function __construct(array $config = [])
    {
        $this->records = new RecordCollection($config, []);
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
     * @param bool $secure Enables circular references tracking
     *
     * @return ContainerInterface
     */
    public function container(bool $secure = false): ContainerInterface
    {
        if ($this->container) { return $this->container; }

        return $this->container = $secure
            ? new TrackingContainer($this->records)
            : new Container($this->records);
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

    public function exists(string $name): bool
    {
        return $this->records->has($name);
    }
}
