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
     * ContainerFactory constructor.
     *
     * @param Setup\Record[] $records
     *
     * @throws Exception\InvalidArgumentException | Exception\InvalidIdException
     */
    public function __construct(array $records = [])
    {
        $this->records = $this->recordCollection($records);
        $this->container = new Container($this->records);
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
        return $this->container;
    }

    /**
     * Returns RecordSetup object able to configure Container's
     * Record slot under given name id.
     *
     * @param string $name
     *
     * @return Setup\RecordSetup
     */
    public function entry(string $name): Setup\RecordSetup
    {
        return new Setup\RecordSetup($name, $this->records);
    }

    protected function recordCollection(array $records = [])
    {
        return new Setup\RecordCollection($records);
    }
}
