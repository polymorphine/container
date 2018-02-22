<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Factory;

use Polymorphine\Container\Factory;
use Psr\Container\ContainerInterface;
use Polymorphine\Container\Container;
use Polymorphine\Container\Record;
use Polymorphine\Container\Exception;
use Closure;


class ContainerFactory implements Factory
{
    private $records;

    public function __construct(array $records = []) {
        $this->records = $this->validRecords($records);
    }

    public function container(): ContainerInterface {
        return new Container($this->records);
    }

    public function value($name, $value): void {
        $this->record($name, new Record\DirectRecord($value));
    }

    public function lazy($name, Closure $closure): void {
        $this->record($name, new Record\LazyRecord($closure));
    }

    public function record($name, Record $record): void {
        $id = $this->validId($name);
        $this->records[$id] = $record;
    }

    private function validId($id): string {
        if (empty($id) || is_numeric($id)) {
            throw new Exception\InvalidIdException('Numeric id tokens are not supported');
        }

        if (array_key_exists($id, $this->records)) {
            throw new Exception\InvalidIdException(sprintf('Record id `%s` already defined', $id));
        }

        return $id;
    }

    private function validRecords(array $records): array {
        foreach ($records as &$record) {
            if (!$record instanceof Record) {
                $record = new Record\DirectRecord($record);
            }
        }

        return $records;
    }
}
