<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Exception;

use LogicException;


class IntegrityConstraintException extends LogicException
{
    /**
     * @param string $id
     *
     * @return static
     */
    public static function alreadyDefined(string $id): self
    {
        return new self("Cannot overwrite defined `$id` entry");
    }

    /**
     * @param string $prefix
     *
     * @return static
     */
    public static function prefixConflict(string $prefix): self
    {
        return new self("Record id prefix `$prefix` already used by stored container");
    }

    /**
     * @param string $separator
     * @param string $id
     *
     * @return static
     */
    public static function unexpectedPrefixSeparator(string $separator, string $id): self
    {
        return new self("Container id cannot contain `$separator` separator - `$id` id given");
    }

    /**
     * @param string $id
     *
     * @return static
     */
    public static function missingReference(string $id): self
    {
        return new self("Wrapped `$id` entry should be referenced by decorating object");
    }
}
