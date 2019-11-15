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

use Psr\Container\ContainerExceptionInterface;
use InvalidArgumentException;


class InvalidTypeException extends InvalidArgumentException implements ContainerExceptionInterface
{
    public static function recordExpected(string $id): self
    {
        return new self("Setup constructor expected instance of Record as records `$id` value");
    }

    public static function containerExpected(string $id): self
    {
        return new self("Setup constructor expected instance of ContainerInterface as containers `$id` value");
    }
}