<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Fixtures\Example;

use Polymorphine\Container\Tests\Fixtures\Example;


class ExampleClass implements Example
{
    private $name;
    private $callback;

    public function __construct(callable $callback, string $name)
    {
        $this->name     = $name;
        $this->callback = $callback;
    }

    public function beNice()
    {
        return ($this->callback)($this->name);
    }
}
