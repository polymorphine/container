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


class RecordCollection
{
    const SEPARATOR = '.';

    private $config;
    private $records;

    /**
     * @param array    $config
     * @param Record[] $records
     *
     * @throws Exception\InvalidArgumentException | Exception\InvalidIdException
     */
    public function __construct(array $config = [], array $records = [])
    {
        $this->validateRecords($records);
        $this->records = $records;
        $this->config  = $config;
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
        return $name && $name[0] === self::SEPARATOR
            ? $this->inConfig($name)
            : isset($this->records[$name]);
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
        if (isset($this->records[$name])) { return $this->records[$name]; }
        if ($name && $name[0] === self::SEPARATOR) {
            return $this->fromConfig($name);
        }

        throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $name));
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
        $this->preventOverwrite($name);
        $this->records[$name] = $record;
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

    private function preventOverwrite(string $id): void
    {
        if ($id && $id[0] === self::SEPARATOR) {
            $message = 'Id starting with separator `%s` is reserved for immutable configuration';
            throw new Exception\InvalidIdException(sprintf($message, self::SEPARATOR));
        }

        if (isset($this->records[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $id));
        }
    }

    private function validateRecords(array $records): void
    {
        foreach ($records as $id => $record) {
            if (!$record instanceof Record) {
                throw new Exception\InvalidArgumentException('Expected associative array of Record instances');
            }
        }
    }

    private function inConfig(string $path): bool
    {
        $data = &$this->config;
        $keys = explode(self::SEPARATOR, substr($path, 1));
        foreach ($keys as $id) {
            if (!is_array($data) || !array_key_exists($id, $data)) {
                return false;
            }
            $data = &$data[$id];
        }

        return true;
    }

    private function fromConfig(string $path): Record
    {
        $data = &$this->config;
        $keys = explode(self::SEPARATOR, substr($path, 1));
        foreach ($keys as $id) {
            if (!is_array($data) || !array_key_exists($id, $data)) {
                throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $path));
            }
            $data = &$data[$id];
        }

        return new Record\ValueRecord($data);
    }
}
