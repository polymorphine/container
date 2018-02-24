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

use Polymorphine\Container\Exception\InvalidArgumentException;
use Polymorphine\Container\Exception\InvalidIdException;


trait ArgumentValidationMethods
{
    private function checkRecords(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->checkIdFormat($id);
            if (!$record instanceof Record) {
                throw new InvalidArgumentException('Container requires array of Record instances');
            }
        }
    }

    private function checkIdFormat(string $id): void
    {
        if (empty($id) || is_numeric($id)) {
            throw new InvalidIdException('Numeric id tokens are not supported');
        }
    }

    private function checkIdExists(string $id): void
    {
        if (array_key_exists($id, $this->records)) {
            throw new InvalidIdException(sprintf('Record id `%s` already defined', $id));
        }
    }
}
