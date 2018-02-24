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


class Container implements ContainerInterface
{
    private $records;

    public function __construct(array $records = []) {
        $this->records = $this->validRecords($records);
    }

    public function get($id) {
        if (!$this->has($id)) {
            throw new Exception\EntryNotFoundException();
        }

        return $this->recordValue($this->records[$id]);
    }

    public function has($id): bool {
        if (empty($id) || is_numeric($id)) {
            throw new Exception\InvalidIdException('Only non numeric string tokens accepted');
        }

        return array_key_exists($id, $this->records);
    }

    private function recordValue(Record $record) {
        return $record->value($this);
    }

    private function validRecords(array $records) {
        foreach ($records as $id => $record) {
            if (is_numeric($id)) {
                throw new Exception\InvalidIdException('Only non numeric string id allowed');
            }

            if (!$record instanceof Record) {
                throw new Exception\InvalidStateException('Only Container\Record type values are allowed');
            }
        }

        return $records;
    }
}