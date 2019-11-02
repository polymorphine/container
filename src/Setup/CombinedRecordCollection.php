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
use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


/**
 * RecordCollection with secondary Container accessed using prefixed ids.
 */
class CombinedRecordCollection implements Records
{
    private $records;
    private $config;
    private $prefix;
    private $prefixLength;

    /**
     * @param Records            $records
     * @param ContainerInterface $config
     * @param string             $prefix
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(Records $records, ContainerInterface $config, string $prefix = '.')
    {
        $this->records = $records;
        $this->config  = $config;
        $this->prefix  = $prefix;

        $this->prefixLength = strlen($prefix);
    }

    public function has(string $id): bool
    {
        return $this->isConfigId($id)
            ? $this->config->has($this->removePrefix($id))
            : $this->records->has($id);
    }

    public function get(string $id, ContainerInterface $container)
    {
        return $this->isConfigId($id)
            ? $this->config->get($this->removePrefix($id))
            : $this->records->get($id, $container);
    }

    public function add(string $id, Record $record): void
    {
        if ($this->isConfigId($id)) {
            $message = 'Id starting with `%s` prefix is used by secondary container';
            throw new Exception\InvalidIdException(sprintf($message, $this->prefix));
        }

        $this->records->add($id, $record);
    }

    public function moveRecord(string $id): string
    {
        return $this->records->moveRecord($id);
    }

    private function isConfigId(string $id): bool
    {
        return strncmp($this->prefix, $id, $this->prefixLength) === 0;
    }

    private function removePrefix(string $id): string
    {
        return substr($id, $this->prefixLength);
    }
}
