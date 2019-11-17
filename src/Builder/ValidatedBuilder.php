<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Builder;

use Polymorphine\Container\Builder;
use Polymorphine\Container\Records;
use Polymorphine\Container\Exception;
use Polymorphine\Container\CompositeContainer;
use Psr\Container\ContainerInterface;


class ValidatedBuilder extends Builder
{
    private $allowOverwrite;
    private $reservedIds = [];

    public function __construct(array $records = [], array $containers = [], bool $allowOverwrite = false)
    {
        parent::__construct($records, $containers);
        $this->allowOverwrite = $allowOverwrite;
        $this->validateState();
    }

    public function addRecord(string $id, Records\Record $record): void
    {
        $this->checkRecordId($id);
        if (!$this->allowOverwrite && isset($this->records[$id])) {
            throw Exception\InvalidIdException::alreadyDefined("`$id` record");
        }
        parent::addRecord($id, $record);
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->checkContainerId($id);
        if (!$this->allowOverwrite && isset($this->containers[$id])) {
            throw Exception\InvalidIdException::alreadyDefined("`$id` container");
        }
        parent::addContainer($id, $container);
    }

    protected function records(): Records
    {
        return new Records\TrackedRecords($this->records);
    }

    private function validateState()
    {
        array_map([$this, 'checkRecord'], array_keys($this->records), $this->records);
        array_map([$this, 'checkContainer'], array_keys($this->containers), $this->containers);
    }

    private function checkRecord(string $id, $value): void
    {
        if (!$value instanceof Records\Record) {
            throw Exception\InvalidTypeException::recordExpected($id);
        }
        $this->checkRecordId($id);
    }

    private function checkRecordId(string $id): void
    {
        if (isset($this->containers[$id])) {
            throw Exception\InvalidIdException::alreadyDefined("`$id` container");
        }

        $separator = strpos($id, CompositeContainer::SEPARATOR);
        $reserved  = $separator === false ? $id : substr($id, 0, $separator);
        if (isset($this->containers[$reserved])) {
            throw Exception\InvalidIdException::prefixConflict($reserved);
        }

        $this->reservedIds[$reserved] = true;
    }

    private function checkContainer(string $id, $value): void
    {
        if (!$value instanceof ContainerInterface) {
            throw Exception\InvalidTypeException::containerExpected($id);
        }
        $this->checkContainerId($id);
    }

    private function checkContainerId(string $id): void
    {
        if (strpos($id, CompositeContainer::SEPARATOR) !== false) {
            throw Exception\InvalidIdException::unexpectedPrefixSeparator(CompositeContainer::SEPARATOR, $id);
        }

        if (isset($this->reservedIds[$id])) {
            throw Exception\InvalidIdException::alreadyDefined("`$id` record (or record prefix)");
        }
    }
}
