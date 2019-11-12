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


class InvalidArgumentException extends \InvalidArgumentException implements ContainerExceptionInterface
{
    public static function recordExpected(string $id): self
    {
        $message = 'Setup constructor expected instance of Record as records `%s` value';
        return new self(sprintf($message, $id));
    }

    public static function containerExpected(string $id): self
    {
        $message = 'Setup constructor expected instance of ContainerInterface as containers `%s` value';
        return new self(sprintf($message, $id));
    }
}
