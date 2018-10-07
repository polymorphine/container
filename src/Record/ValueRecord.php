<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Record;

use Polymorphine\Container\Record;
use Psr\Container\ContainerInterface;


/**
 * Record that returns its property value directly.
 */
class ValueRecord implements Record
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function value(ContainerInterface $container)
    {
        return $this->value;
    }
}
