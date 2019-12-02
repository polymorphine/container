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
    private const ITEM_RECORD    = 'record';
    private const ITEM_CONTAINER = 'container';

    private $reservedIds = [];

    public function __construct(array $records = [], array $containers = [])
    {
        parent::__construct($records, $containers);
        $this->validateState();
    }

    public function addRecord(string $id, Records\Record $record): void
    {
        $this->checkRecordId($id);
        if (isset($this->records[$id])) {
            $this->implicitOverwrite($id, self::ITEM_RECORD);
        }
        parent::addRecord($id, $record);
    }

    public function addContainer(string $id, ContainerInterface $container): void
    {
        $this->checkContainerId($id);
        if (isset($this->containers[$id])) {
            $this->implicitOverwrite($id, self::ITEM_CONTAINER);
        }
        parent::addContainer($id, $container);
    }

    public function replaceRecord(string $id, Records\Record $record): void
    {
        $this->checkRecordId($id);
        if (!isset($this->records[$id])) {
            $this->replaceUndefined($id, self::ITEM_RECORD);
        }
        parent::replaceRecord($id, $record);
    }

    public function replaceContainer(string $id, ContainerInterface $container): void
    {
        $this->checkContainerId($id);
        if (!isset($this->containers[$id])) {
            $this->replaceUndefined($id, self::ITEM_CONTAINER);
        }
        parent::replaceContainer($id, $container);
    }

    protected function records(): Records
    {
        return new Records\TrackedRecords($this->records);
    }

    protected function implicitOverwrite(string $id, string $item): void
    {
        throw Exception\IntegrityConstraintException::alreadyDefined("`$id` $item");
    }

    protected function replaceUndefined(string $id, string $item): void
    {
        throw Exception\IntegrityConstraintException::undefined("`$id` $item");
    }

    private function validateState()
    {
        foreach ($this->records as $id => $record) {
            $this->checkRecord($id, $record);
        }

        foreach ($this->containers as $id => $container) {
            $this->checkContainer($id, $container);
        }
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
            throw Exception\IntegrityConstraintException::alreadyDefined("`$id` container");
        }

        $separator = strpos($id, CompositeContainer::SEPARATOR);
        $reserved  = $separator === false ? $id : substr($id, 0, $separator);
        if (isset($this->containers[$reserved])) {
            throw Exception\IntegrityConstraintException::prefixConflict($reserved);
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
            throw Exception\IntegrityConstraintException::unexpectedPrefixSeparator(CompositeContainer::SEPARATOR, $id);
        }

        if (isset($this->reservedIds[$id])) {
            throw Exception\IntegrityConstraintException::alreadyDefined("`$id` record (or record prefix)");
        }
    }
}
