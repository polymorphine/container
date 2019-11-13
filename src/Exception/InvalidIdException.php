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


class InvalidIdException extends InvalidArgumentException implements ContainerExceptionInterface
{
    public static function alreadyDefined(string $resource): self
    {
        $message = 'Cannot overwrite defined %s';
        return new self(sprintf($message, $resource));
    }

    public static function prefixConflict(string $prefix): self
    {
        $message = 'Record id prefix `%s` already used by stored container';
        return new self(sprintf($message, $prefix));
    }

    public static function unexpectedPrefixSeparator(string $separator, string $id): self
    {
        $message = 'Container id cannot contain `%s` separator - `%s` id given';
        return new self(sprintf($message, $separator, $id));
    }
}
