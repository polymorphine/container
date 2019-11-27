<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Setup;

use Polymorphine\Container\Tests\SetupTest;
use Polymorphine\Container\Setup;
use Polymorphine\Container\Records;


class BasicSetupTest extends SetupTest
{
    protected function builder(array $records = [], array $containers = []): Setup
    {
        return Setup::basic($records, $containers);
    }

    protected function records(array $records = []): Records
    {
        return new Records($records);
    }
}
