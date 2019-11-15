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


class TrackedRecordNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    use CallStackMessageMethod;

    public function __construct(string $message = '', array $callStack = [])
    {
        parent::__construct(self::extendMessage($message, $callStack));
    }
}