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


/**
 * Container of Record strategies to produce container values.
 */
class Records
{
    private $records;

    /**
     * @param Records\Record[] $records Associative (flat) array of Record entries
     */
    public function __construct(array $records = [])
    {
        $this->records = $records;
    }

    /**
     * Checks if Record is stored at given identifier without
     * calling for its value.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->records[$id]);
    }

    /**
     * Returns Record value stored at given identifier.
     * ContainerInterface is given for possibility of producing
     * Record's value using another container entries.
     *
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Exception\RecordNotFoundException
     *
     * @return mixed
     */
    public function get(string $id, ContainerInterface $container)
    {
        if (!isset($this->records[$id])) {
            throw Exception\RecordNotFoundException::undefined($id);
        }
        return $this->records[$id]->value($container);
    }
}
