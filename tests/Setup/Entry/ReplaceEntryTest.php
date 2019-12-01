<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Setup\Entry;

use Polymorphine\Container\Tests\Setup\EntryTest;
use Polymorphine\Container\Setup\Entry;
use Polymorphine\Container\Tests\Doubles;


class ReplaceEntryTest extends EntryTest
{
    protected function entry(string $id, Doubles\MockedBuild $build = null): Entry
    {
        return new Entry\ReplaceEntry($id, $build ?? $this->builder());
    }

    protected function builder(): Doubles\MockedBuild
    {
        return Doubles\MockedBuild::replaced();
    }
}
