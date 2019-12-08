<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Entry;

use Polymorphine\Container\Setup\Entry;
use Polymorphine\Container\Setup\Exception;


class AddEntry extends Entry
{
    protected function hasWriteAccess(): bool
    {
        if ($this->builder->has($this->id)) {
            throw Exception\IntegrityConstraintException::alreadyDefined($this->id);
        }

        return true;
    }
}
