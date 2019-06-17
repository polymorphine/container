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

use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


/**
 * RecordCollection with secondary Container accessed using prefixed ids
 */
class CombinedRecordCollection extends RecordCollection
{
    private $config;
    private $prefix;

    /**
     * @param Record[]           $records
     * @param ContainerInterface $config
     * @param string             $prefix
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(array $records, ContainerInterface $config, string $prefix = '.')
    {
        $this->config = $config;
        $this->prefix = $prefix;
        parent::__construct($records);
    }

    public function has(string $id): bool
    {
        return $this->isConfigId($id)
            ? $this->config->has($this->removePrefix($id))
            : parent::has($id);
    }

    public function get(string $id, ContainerInterface $container)
    {
        return $this->isConfigId($id)
            ? $this->config->get($this->removePrefix($id))
            : parent::get($id, $container);
    }

    public function add(string $id, Record $record): void
    {
        if ($this->isConfigId($id)) {
            $message = 'Id starting with `%s` prefix is used by secondary container';
            throw new Exception\InvalidIdException(sprintf($message, $this->prefix));
        }

        parent::add($id, $record);
    }

    public function moveRecord(string $id): string
    {
        return parent::moveRecord($id);
    }

    private function isConfigId(string $id): bool
    {
        return strpos($id, $this->prefix) === 0;
    }

    private function removePrefix(string $id): string
    {
        return substr($id, strlen($this->prefix));
    }
}
