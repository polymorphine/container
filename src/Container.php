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
use Polymorphine\Container\Exception\EntryNotFoundException;


class Container implements ContainerInterface
{
    use ArgumentValidationMethods;

    private $records;

    public function __construct(array $records = []) {
        $this->checkRecords($records);
        $this->records = $records;
    }

    public function get($id) {
        if (!$this->has($id)) {
            throw new EntryNotFoundException();
        }

        return $this->recordValue($this->records[$id]);
    }

    public function has($id): bool {
        $this->checkIdFormat($id);

        return array_key_exists($id, $this->records);
    }

    private function recordValue(Record $record) {
        return $record->value($this);
    }
}
