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
use Polymorphine\Container\Setup\Exception;
use Polymorphine\Container\Tests\Doubles;


class ReplaceEntryTest extends EntryTest
{
    public function testReplaceEntryWithUndefinedId_container_ThrowsException()
    {
        $setup = Doubles\MockedBuild::undefined();
        $entry = $this->entry('foo', $setup);
        $this->expectException(Exception\IntegrityConstraintException::class);
        $entry->container(Doubles\FakeContainer::new());
    }

    public function testReplaceEntryWithUndefinedId_record_ThrowsException()
    {
        $setup = Doubles\MockedBuild::undefined();
        $entry = $this->entry('foo', $setup);
        $this->expectException(Exception\IntegrityConstraintException::class);
        $entry->record(Doubles\MockedRecord::new());
    }

    protected function entry(string $id, Doubles\MockedBuild $build = null): Entry
    {
        return new Entry\ReplaceEntry($id, $build ?? $this->builder());
    }

    protected function builder(): Doubles\MockedBuild
    {
        return Doubles\MockedBuild::defined();
    }
}
