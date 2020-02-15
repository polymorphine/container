<?php

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


class OverwriteRuleException extends LogicException
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
}
