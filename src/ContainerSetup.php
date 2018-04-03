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
    }

    /**
     * Creates new Container instance with provided records.
     * After that ContainerSetup gets back to is default state
     * with empty RecordCollection and is ready to build
     * another Container instance.
     *
     * Immutability of container depends on stored records
     * implementation, because although no new entries can
     * be added, side-effects can change subsequent call
     * outcomes for stored identifiers.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        $container     = new Container($this->records);
        $this->records = $this->recordCollection();

        return $container;
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
