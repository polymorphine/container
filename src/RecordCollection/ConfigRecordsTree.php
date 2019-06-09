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


class ConfigRecordsTree implements RecordCollection
{
    public const SEPARATOR = '.';

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $path): Record
    {
        $data = &$this->config;
        foreach (explode(self::SEPARATOR, $path) as $id) {
            if (!is_array($data) || !array_key_exists($id, $data)) {
                throw new Exception\RecordNotFoundException();
            }
            $data = &$data[$id];
        }

        return new Record\ValueRecord($data);
    }

    public function has(string $path): bool
    {
        $data = &$this->config;
        foreach (explode(self::SEPARATOR, $path) as $id) {
            if (!is_array($data) || !array_key_exists($id, $data)) {
                return false;
            }
            $data = &$data[$id];
        }

        return true;
    }
}
