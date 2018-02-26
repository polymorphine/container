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
use Polymorphine\Container\Exception\InvalidArgumentException;
use Polymorphine\Container\Exception\InvalidIdException;


class Factory
{
    use ArgumentValidationMethods;

    private $records;

    /**
     * ContainerFactory constructor.
     *
     * @param Record[] $records
     *
     * @throws InvalidArgumentException|InvalidIdException
     */
    public function __construct(array $records = []) {
        $this->checkRecords($records);
        $this->records = $records;
    }

    /**
     * Creates container with provided records.
     *
     * Immutability of container depends on stored records
     * implementation, because although no new entries can
     * be added, side-effects can change subsequent call
     * outcomes for stored identifiers.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface {
        return new Container($this->records);
    }

    /**
     * Stores Record under given $name identifier.
     * Behavior of Container returning given Record's value
     * depends on passed Record's implementation.
     *
     * @param $name
     * @param Record $record
     *
     * @throws InvalidIdException
     */
    public function setRecord(string $name, Record $record): void {
        $this->checkIdFormat($name);
        $this->checkIdExists($name);
        $this->records[$name] = $record;
    }

    /**
     * Returns write-only proxy with helper methods to instantiate
     * Record implementations under given registry key.
     *
     * @param string $name
     *
     * @return RecordEntry
     */
    public function recordEntry(string $name): RecordEntry {
        return new RecordEntry($name, $this);
    }
}
