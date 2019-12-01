<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Setup\Build;

use Polymorphine\Container\Tests\Setup\BuildTest;
use Polymorphine\Container\Records;
use Polymorphine\Container\Setup;


class BasicBuildTest extends BuildTest
{
    protected function builder(array $records = [], array $containers = []): Setup\Build
    {
        return new Setup\Build\BasicBuild($records, $containers);
    }

    protected function records(array $records = []): Records
    {
        return new Records($records);
    }
}
