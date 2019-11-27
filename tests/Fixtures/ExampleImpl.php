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


class ExampleImpl implements Example
{
    private $string;

    public function __construct(callable $callback, string $name)
    {
        $this->string = $callback($name);
    }

    public static function new(string $string = 'Example')
    {
        return new self(function (string $string) { return $string; }, $string);
    }

    public function getString(): string
    {
        return $this->string;
    }
}
