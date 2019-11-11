<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup;

use Polymorphine\Container\Records;
use Polymorphine\Container\RecordContainer;
use Polymorphine\Container\CompositeContainer;
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class ValidatedCollection extends Collection
{
    private $reservedIds = [];

    public function __construct(array $records = [], array $containers = [])
    {
        parent::__construct($records, $containers);
        $this->validateState();
    }

    public function container(): ContainerInterface
    {
        return $this->containers
            ? new CompositeContainer(new Records\TrackedRecords($this->records), $this->containers)
            : new RecordContainer(new Records\TrackedRecords($this->records));
    }

    public function add(string $id, Records\Record $record): void
    {
        $this->checkRecordId($id);
        parent::add($id, $record);
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->checkContainerId($id);
        parent::addContainer($id, $container);
    }

    private function validateState()
    {
        array_map([$this, 'checkRecord'], array_keys($this->records), $this->records);
        array_map([$this, 'checkContainer'], array_keys($this->containers), $this->containers);
    }

    private function checkRecord(string $id, $value): void
    {
        if (!$value instanceof Records\Record) {
            $message = 'Setup record expected instance of Record in `%s` field';
            throw new Exception\InvalidArgumentException(sprintf($message, $id));
        }
        $this->checkRecordId($id);
    }

    private function checkRecordId(string $id): void
    {
        $separator = strpos($id, self::SEPARATOR);
        $prefix    = $separator === false ? $id : substr($id, 0, $separator);
        if (isset($this->containers[$prefix])) {
            $message = 'Record id or prefix `%s` already used by stored Container';
            throw new Exception\InvalidIdException(sprintf($message, $prefix));
        }

        $this->reservedIds[$prefix] = true;
    }

    private function checkContainer(string $id, $value): void
    {
        if (!$value instanceof ContainerInterface) {
            $message = 'Setup config expected instance of ContainerInterface in `%s` field';
            throw new Exception\InvalidArgumentException(sprintf($message, $id));
        }
        $this->checkContainerId($id);
    }

    private function checkContainerId(string $id): void
    {
        if (strpos($id, self::SEPARATOR) !== false) {
            $message = 'Container id cannot contain `%s` separator - `%s` id given';
            throw new Exception\InvalidIdException(sprintf($message, self::SEPARATOR, $id));
        }

        if (isset($this->reservedIds[$id])) {
            $message = 'Container id `%s` already used by some record (possibly as prefix)';
            throw new Exception\InvalidIdException(sprintf($message, $id));
        }
    }
}
