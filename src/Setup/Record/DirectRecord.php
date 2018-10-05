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
 * Record that returns stored values as they were passed into constructor.
 */
class DirectRecord implements Record
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
