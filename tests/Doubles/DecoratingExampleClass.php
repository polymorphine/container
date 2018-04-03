<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Doubles;


class DecoratingExampleClass implements Example
{
    private $decorated;
    private $sentence;

    public function __construct(Example $decorated, string $sentence)
    {
        $this->decorated = $decorated;
        $this->sentence  = $sentence;
    }

    public function beNice()
    {
        return $this->decorated->beNice() . ' ' . $this->sentence;
    }
}
