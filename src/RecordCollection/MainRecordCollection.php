<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\RecordCollection;

use Polymorphine\Container\RecordCollection;
use Polymorphine\Container\Record;
use Polymorphine\Container\Exception;


class MainRecordCollection implements RecordCollection
{
    private $records = [];

    /**
     * @param Record[] $records
     *
     * @throws Exception\InvalidArgumentException | Exception\InvalidIdException
     */
    public function __construct(array $records = [])
    {
        $this->validateRecords($records);
        $this->records = $records;
    }

    /**
     * Checks if Record is stored under given name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->records[$name]);
    }

    /**
     * Stores Record under given $name identifier.
     * Behavior of Container returning given Record's value
     * depends on passed Record's implementation.
     *
     * @param $name
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function add(string $name, Record $record): void
    {
        $this->validateIdFormat($name);
        $this->preventOverwrite($name);
        $this->records[$name] = $record;
    }

    /**
     * Returns Record stored under given $name identifier.
     *
     * @param string $name
     *
     * @throws Exception\RecordNotFoundException
     *
     * @return Record
     */
    public function get(string $name): Record
    {
        $this->validateIdFormat($name);
        if (!isset($this->records[$name])) {
            throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $name));
        }

        return $this->records[$name];
    }

    /**
     * Removes Record with given $name identifier.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function remove(string $name): void
    {
        unset($this->records[$name]);
    }

    private function validateRecords(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->validateIdFormat($id);
            if (!$record instanceof Record) {
                throw new Exception\InvalidArgumentException('Expected associative array of Record instances');
            }
        }
    }

    private function validateIdFormat(string $id): void
    {
        if (empty($id) || is_numeric($id)) {
            throw new Exception\InvalidIdException('Numeric id tokens for Container Records are not supported');
        }
    }

    private function preventOverwrite(string $id): void
    {
        if (isset($this->records[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $id));
        }
    }
}
