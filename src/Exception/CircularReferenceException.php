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
use LogicException;


class CircularReferenceException extends LogicException implements ContainerExceptionInterface
{
    use CallStackMessageMethod;

    public function __construct(string $id, array $callStack)
    {
        $message = "Lazy composition of `$id` record is using reference to itself";
        parent::__construct(self::extendMessage($message, $callStack, $id));
    }
}
