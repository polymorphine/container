<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Fixtures;


class FactoryExample
{
    public function create(string ...$strings): Example
    {
        return ExampleImpl::new(implode(' ', $strings));
    }
}
