<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Build;

use Polymorphine\Container\Setup\Build;
use Polymorphine\Container\Setup\Exception;
use Polymorphine\Container\Records;
use Polymorphine\Container\CompositeContainer;
use Psr\Container\ContainerInterface;


class ValidatedBuild extends Build
{
    private $reservedIds = [];

    public function __construct(array $records = [], array $containers = [])
    {
        parent::__construct($this->validRecords($records), $this->validContainers($containers));
    }

    public function setRecord(string $id, Records\Record $record): void
    {
        $this->checkRecordId($id);
        parent::setRecord($id, $record);
    }

    public function setContainer(string $id, ContainerInterface $container): void
    {
        $this->checkContainerId($id);
        parent::setContainer($id, $container);
    }

    protected function records(): Records
    {
        return new Records\TrackedRecords($this->records);
    }

    private function checkRecordId(string $id): void
    {
        $separator = strpos($id, CompositeContainer::SEPARATOR);
        if (!$separator) { return; }

        $reserved = substr($id, 0, $separator);
        if (isset($this->containers[$reserved])) {
            throw Exception\IntegrityConstraintException::prefixConflict($reserved);
        }

        $this->reservedIds[$reserved] = true;
    }

    private function checkContainerId(string $id): void
    {
        if (strpos($id, CompositeContainer::SEPARATOR) !== false) {
            throw Exception\IntegrityConstraintException::unexpectedPrefixSeparator(CompositeContainer::SEPARATOR, $id);
        }

        if (isset($this->reservedIds[$id])) {
            throw Exception\IntegrityConstraintException::alreadyDefined($id);
        }
    }

    private function validRecords(array $records): array
    {
        foreach ($records as $id => $record) {
            $this->checkRecordId($id);
            if (!$record instanceof Records\Record) {
                throw Exception\InvalidTypeException::recordExpected($id);
            }
        }
        return $records;
    }

    private function validContainers(array $containers): array
    {
        foreach ($containers as $id => $container) {
            $this->checkContainerId($id);
            if (!$container instanceof ContainerInterface) {
                throw Exception\InvalidTypeException::containerExpected($id);
            }
        }
        return $containers;
    }
}
