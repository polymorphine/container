<?php

/**
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Record;

use Psr\Container\ContainerInterface;
use Polymorphine\Container\Record;
use Closure;


class LazyRecord implements Record
{
    private $value;
    private $callback;

    public function __construct(Closure $callback) {
        $this->callback = $callback;
    }

    public function value(ContainerInterface $c) {
        return isset($this->value) ? $this->value : $this->value = $this->callback->__invoke($c);
    }
}
