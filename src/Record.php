<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container;

use Psr\Container\ContainerInterface;


interface Record
{
    /**
     * Unwraps value requested from container.
     *
     * Container instance is passed as parameter as returned value
     * may depend on other Container's entries.
     *
     * @param ContainerInterface $c
     *
     * @return mixed unwrapped record value
     */
    public function value(ContainerInterface $c);
}
