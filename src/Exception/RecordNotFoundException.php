<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;


class RecordNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    public static function undefined(string $id): self
    {
        return new self("Record `$id` not defined");
    }

    public static function cannotWrap(string $id): self
    {
        throw new self("Attempted to decorate non-existent `$id` record with new composition");
    }
}
