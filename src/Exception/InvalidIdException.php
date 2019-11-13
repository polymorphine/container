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
        return new self("Cannot overwrite defined $resource");
    }

    public static function prefixConflict(string $prefix): self
    {
        return new self("Record id prefix `$prefix` already used by stored container");
    }

    public static function unexpectedPrefixSeparator(string $separator, string $id): self
    {
        return new self("Container id cannot contain `$separator` separator - `$id` id given");
    }
}
