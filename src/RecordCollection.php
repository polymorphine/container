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

use Polymorphine\Container\Record\ValueRecord;


class RecordCollection
{
    const SEPARATOR = '.';

    private $config;
    private $records;

    /**
     * Config can be multidimensional array which values would be
     * accessed using path notation, therefore config array keys
     * cannot contain path separator.
     *
     * @param array    $config  Associative (multidimensional) array of configuration values
     * @param Record[] $records Associative (flat) array of Record entries
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $config = [], array $records = [])
    {
        $this->records = $this->validRecordsArray($records);
        $this->config  = $config;
    }

    /**
     * Checks if Record is stored at given name identifier.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->isConfigId($name)
            ? $this->configHas($name)
            : isset($this->records[$name]);
    }

    /**
     * Returns Record stored at given $name identifier.
     *
     * @param string $name
     *
     * @throws Exception\RecordNotFoundException
     *
     * @return Record
     */
    public function get(string $name): Record
    {
        if ($this->isConfigId($name)) { return $this->configGet($name); }

        if (!isset($this->records[$name])) {
            throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $name));
        }
        return $this->records[$name];
    }

    /**
     * Stores Record at given $name identifier.
     *
     * @param $name
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function add(string $name, Record $record): void
    {
        if ($this->isConfigId($name)) {
            $message = 'Id starting with separator `%s` is reserved for immutable configuration';
            throw new Exception\InvalidIdException(sprintf($message, self::SEPARATOR));
        }

        if (isset($this->records[$name])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $name));
        }

        $this->records[$name] = $record;
    }

    /**
     * Removes Record stored at given $name identifier.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function remove(string $name): void
    {
        unset($this->records[$name]);
    }

    private function isConfigId(string $name)
    {
        return $name && $name[0] === self::SEPARATOR;
    }

    private function configHas(string $path): bool
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

    private function configGet(string $path)
    {
        $data = &$this->config;
        $keys = explode(self::SEPARATOR, substr($path, 1));
        foreach ($keys as $id) {
            if (!is_array($data) || !array_key_exists($id, $data)) {
                throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $path));
            }
            $data = &$data[$id];
        }

        return new ValueRecord($data);
    }

    private function validRecordsArray(array $records): array
    {
        foreach ($records as $id => $record) {
            if ($record instanceof Record) { continue; }
            throw new Exception\InvalidArgumentException('Expected flat associative array of Record instances');
        }
        return $records;
    }
}
