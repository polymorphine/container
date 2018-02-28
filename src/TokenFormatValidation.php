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

use Polymorphine\Container\Exception\InvalidIdException;


trait TokenFormatValidation
{
    private function validateIdFormat(string $id): void
    {
        if (empty($id) || is_numeric($id)) {
            throw new InvalidIdException('Numeric id tokens for Container Records are not supported');
        }
    }
}
