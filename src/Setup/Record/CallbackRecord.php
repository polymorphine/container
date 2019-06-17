<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Record;

use Polymorphine\Container\Setup\Record;
use Psr\Container\ContainerInterface;


/**
 * Record that returns value invoked from callable property.
 *
 * Returned value is cached and returned directly when
 * value() method is called again.
 */
class CallbackRecord implements Record
{
    private $value;
    private $callback;

    /**
     * @param callable $callback function (ContainerInterface): mixed
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function value(ContainerInterface $container)
    {
        return $this->value ?: $this->value = ($this->callback)($container);
    }
}
