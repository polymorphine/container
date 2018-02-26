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
    private function validateRecords(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->validateIdFormat($id);
            if (!$record instanceof Record) {
                throw new InvalidArgumentException('Container requires associative array of Record instances');
            }
        }
    }

    private function validateIdFormat(string $id): void
    {
        if (empty($id) || is_numeric($id)) {
            throw new InvalidIdException('Numeric id tokens for Container Records are not supported');
        }
    }

    private function checkIdOverwrite(string $id): void
    {
        if (isset($this->records[$id])) {
            throw new InvalidIdException(sprintf('Record id `%s` already defined - overwrite not allowed', $id));
        }
    }
}
