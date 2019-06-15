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


class RecordCollection
{
    private $records;
    private $config;

    /**
     * @param Record[]                $records Associative (flat) array of Record entries
     * @param null|ContainerInterface $config
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $records = [], ?ContainerInterface $config = null)
    {
        $this->records = $this->validRecordsArray($records);
        $this->config  = $config;
    }

    /**
     * Checks if Record is stored at given identifier.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return $this->isConfigId($id)
            ? $this->config->has(substr($id, 1))
            : isset($this->records[$id]);
    }

    /**
     * Returns Record stored at given identifier.
     *
     * @param string             $id
     * @param ContainerInterface $container
     *
     *@throws Exception\RecordNotFoundException
     *
     * @return mixed
     */
    public function get(string $id, ContainerInterface $container)
    {
        return $this->isConfigId($id)
            ? $this->config->get(substr($id, 1))
            : $this->getRecord($id)->value($container);
    }

    /**
     * Stores Record at given $name identifier.
     *
     * @param $id
     * @param Record $record
     *
     * @throws Exception\InvalidIdException
     */
    public function add(string $id, Record $record): void
    {
        if ($this->isConfigId($id)) {
            $message = 'Id starting with separator `%s` is reserved for immutable configuration';
            throw new Exception\InvalidIdException(sprintf($message, ConfigContainer::SEPARATOR));
        }

        if (isset($this->records[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $id));
        }

        $this->records[$id] = $record;
    }

    /**
     * Moves Record to different identifier.
     *
     * @param string $id
     *
     * @return string New identifier of moved Record
     */
    public function moveRecord(string $id): string
    {
        if (!isset($this->records[$id])) {
            $message = 'Undefined `%s` record cannot be moved';
            throw new Exception\RecordNotFoundException(sprintf($message, $id));
        }

        $newId = $id . '.WRAP';
        while (isset($this->records[$newId])) {
            $newId .= '.WRAP';
        }

        $this->records[$newId] = $this->records[$id];
        unset($this->records[$id]);

        return $newId;
    }

    private function getRecord(string $id): Record
    {
        if (!isset($this->records[$id])) {
            throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $id));
        }
        return $this->records[$id];
    }

    private function isConfigId(string $id): bool
    {
        return $this->config && $id && $id[0] === ConfigContainer::SEPARATOR;
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
